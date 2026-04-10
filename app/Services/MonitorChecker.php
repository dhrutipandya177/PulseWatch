<?php

namespace App\Services;

use App\Models\Tenant\Monitor;
use App\Models\Tenant\MonitorLog;
use App\Models\Tenant\Component;
use App\Models\Tenant\Incident;
use App\Models\Tenant\IncidentUpdate;
use App\Models\Tenant\SslCertificate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class MonitorChecker
{
    public function check(Monitor $monitor): MonitorLog
    {
        $startedAt = microtime(true);

        if ($monitor->type === Monitor::TYPE_HTTP) {
            return $this->checkHttp($monitor, $startedAt);
        }

        if ($monitor->type === Monitor::TYPE_PING) {
            return $this->checkPing($monitor, $startedAt);
        }

        if ($monitor->type === Monitor::TYPE_PORT) {
            return $this->checkPort($monitor, $startedAt);
        }

        return $this->createFailedLog($monitor, 'Unknown monitor type');
    }

    protected function checkHttp(Monitor $monitor, float $startedAt): MonitorLog
    {
        try {
            $request = Http::timeout($monitor->timeout_seconds)
                ->withHeaders($monitor->headers ?? []);

            if ($monitor->follow_redirects) {
                $request = $request->withoutRedirecting();
            }

            if (!$monitor->verify_ssl) {
                $request = $request->withoutVerifying();
            }

            $method = strtolower($monitor->method);
            $response = $request->{$method}($monitor->url);

            $responseTimeMs = round((microtime(true) - $startedAt) * 1000);
            $statusCode = $response->status();
            $isSuccess = $statusCode === $monitor->expected_status_code;

            $contentMatch = true;
            if ($monitor->expected_content) {
                foreach ($monitor->expected_content as $pattern) {
                    if (!str_contains($response->body(), $pattern)) {
                        $contentMatch = false;
                        break;
                    }
                }
            }

            $isSuccess = $isSuccess && $contentMatch;

            $log = MonitorLog::create([
                'monitor_id' => $monitor->id,
                'status' => $isSuccess,
                'response_time_ms' => $responseTimeMs,
                'status_code' => $statusCode,
                'error_message' => $contentMatch ? null : 'Expected content not found',
                'is_incident' => !$isSuccess,
                'checked_at' => now(),
            ]);

            $this->updateMonitorStatus($monitor, $isSuccess, $responseTimeMs, $statusCode, null);
            $this->checkSslCertificate($monitor, $response);

            if (!$isSuccess) {
                $this->maybeCreateInc($monitor);
            }

            return $log;
        } catch (\Exception $e) {
            $responseTimeMs = round((microtime(true) - $startedAt) * 1000);

            $log = MonitorLog::create([
                'monitor_id' => $monitor->id,
                'status' => false,
                'response_time_ms' => $responseTimeMs,
                'status_code' => null,
                'error_message' => $e->getMessage(),
                'is_incident' => true,
                'checked_at' => now(),
            ]);

            $this->updateMonitorStatus($monitor, false, $responseTimeMs, null, $e->getMessage());
            $this->maybeCreateInc($monitor);

            return $log;
        }
    }

    protected function checkPing(Monitor $monitor, float $startedAt): MonitorLog
    {
        $host = parse_url($monitor->url, PHP_URL_HOST) ?? $monitor->url;
        $exec = sprintf('ping -c 1 -W %d %s 2>&1', $monitor->timeout_seconds, escapeshellarg($host));

        $output = [];
        $returnCode = 0;
        exec($exec, $output, $returnCode);

        $responseTimeMs = round((microtime(true) - $startedAt) * 1000);
        $isSuccess = $returnCode === 0;

        $log = MonitorLog::create([
            'monitor_id' => $monitor->id,
            'status' => $isSuccess,
            'response_time_ms' => $responseTimeMs,
            'status_code' => null,
            'error_message' => $isSuccess ? null : 'Ping failed',
            'is_incident' => !$isSuccess,
            'checked_at' => now(),
        ]);

        $this->updateMonitorStatus($monitor, $isSuccess, $responseTimeMs, null, $isSuccess ? null : 'Ping failed');

        if (!$isSuccess) {
            $this->maybeCreateInc($monitor);
        }

        return $log;
    }

    protected function checkPort(Monitor $monitor, float $startedAt): MonitorLog
    {
        $host = parse_url($monitor->url, PHP_URL_HOST) ?? $monitor->url;
        $port = parse_url($monitor->url, PHP_URL_PORT) ?? 80;

        $isSuccess = false;
        $errorMessage = null;

        try {
            $fp = @fsockopen($host, $port, $errno, $errstr, $monitor->timeout_seconds);
            if ($fp !== false) {
                $isSuccess = true;
                fclose($fp);
            } else {
                $errorMessage = $errstr;
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $responseTimeMs = round((microtime(true) - $startedAt) * 1000);

        $log = MonitorLog::create([
            'monitor_id' => $monitor->id,
            'status' => $isSuccess,
            'response_time_ms' => $responseTimeMs,
            'status_code' => null,
            'error_message' => $errorMessage,
            'is_incident' => !$isSuccess,
            'checked_at' => now(),
        ]);

        $this->updateMonitorStatus($monitor, $isSuccess, $responseTimeMs, null, $errorMessage);

        if (!$isSuccess) {
            $this->maybeCreateInc($monitor);
        }

        return $log;
    }

    protected function updateMonitorStatus(
        Monitor $monitor,
        bool $isSuccess,
        int $responseTimeMs,
        ?int $statusCode,
        ?string $errorMessage
    ): void {
        $update = [
            'last_checked_at' => now(),
            'last_status' => $isSuccess,
            'last_response_time_ms' => $responseTimeMs,
        ];

        if ($statusCode !== null) {
            $update['last_status_code'] = $statusCode;
        }

        if (!$isSuccess) {
            $update['last_error_message'] = $errorMessage;
            $update['last_error_at'] = now();
        } else {
            $update['last_error_message'] = null;
            $update['last_error_at'] = null;
        }

        $monitor->update($update);
    }

    protected function checkSslCertificate(Monitor $monitor, $response): void
    {
        if (!$monitor->verify_ssl) {
            return;
        }

        try {
            $url = parse_url($monitor->url);
            $host = $url['host'];
            $port = $url['port'] ?? 443;

            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);

            $result = stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

            if ($result) {
                $params = stream_context_get_params($result);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

                if ($cert) {
                    $validFrom = Carbon::createFromTimestamp($cert['validFrom_time_t']);
                    $validUntil = Carbon::createFromTimestamp($cert['validTo_time_t']);
                    $daysRemaining = now()->diffInDays($validUntil, false);

                    SslCertificate::updateOrCreate(
                        ['monitor_id' => $monitor->id],
                        [
                            'issuer' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? null,
                            'subject' => $cert['subject']['CN'] ?? null,
                            'valid_from' => $validFrom,
                            'valid_until' => $validUntil,
                            'days_remaining' => $daysRemaining,
                            'serial_number' => $cert['serialNumber'] ?? null,
                            'signature_algorithm' => $cert['signatureTypeSN'] ?? null,
                            'is_valid' => $validUntil->isFuture() && $validFrom->isPast(),
                            'last_checked_at' => now(),
                        ]
                    );

                    fclose($result);
                }
            }
        } catch (\Exception $e) {
            // Silently fail SSL checks
        }
    }

    protected function maybeCreateIncident(Monitor $monitor): void
    {
        $component = $monitor->component;

        if (!$component) {
            return;
        }

        // Only auto-create incident if there isn't already an active one for this component
        $activeIncident = $component->activeIncidents()->first();

        if ($activeIncident) {
            return;
        }

        $incident = Incident::create([
            'title' => "Auto-detected: {$monitor->name} is down",
            'description' => "Our monitoring system detected that {$monitor->name} ({$monitor->url}) is not responding as expected.",
            'status' => Incident::STATUS_INVESTIGATING,
            'severity' => Incident::SEVERITY_MAJOR,
            'component_id' => $component->id,
            'notify_subscribers' => true,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'status' => IncidentUpdate::STATUS_INVESTIGATING,
            'message' => "We've detected an issue with {$monitor->name}. Our team is investigating.",
        ]);
    }

    protected function createFailedLog(Monitor $monitor, string $message): MonitorLog
    {
        return MonitorLog::create([
            'monitor_id' => $monitor->id,
            'status' => false,
            'response_time_ms' => null,
            'status_code' => null,
            'error_message' => $message,
            'is_incident' => true,
            'checked_at' => now(),
        ]);
    }
}
