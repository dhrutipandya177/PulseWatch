<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Incident;
use App\Models\Tenant\IncidentUpdate;
use App\Models\Tenant\Component;
use App\Jobs\DispatchIncidentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    public function index()
    {
        $incidents = Incident::with(['component', 'updates'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('tenant.incidents.index', compact('incidents'));
    }

    public function create()
    {
        $components = Component::orderBy('name')->get();

        return view('tenant.incidents.create', compact('components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'severity' => ['required', 'in:none,minor,major,critical'],
            'component_id' => ['nullable', 'exists:components,id'],
            'notify_subscribers' => ['boolean'],
        ]);

        $incident = Incident::create($validated);

        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'status' => $incident->status,
            'message' => 'Incident created.',
            'user_id' => Auth::id(),
        ]);

        if ($incident->notify_subscribers) {
            DispatchIncidentNotification::dispatch($incident);
        }

        return redirect()->route('tenant.incidents.index')
            ->with('success', 'Incident created successfully.');
    }

    public function show(Incident $incident)
    {
        $incident->load(['component', 'updates.user']);

        return view('tenant.incidents.show', compact('incident'));
    }

    public function edit(Incident $incident)
    {
        $components = Component::orderBy('name')->get();

        return view('tenant.incidents.edit', compact('incident', 'components'));
    }

    public function update(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'severity' => ['required', 'in:none,minor,major,critical'],
            'component_id' => ['nullable', 'exists:components,id'],
            'notify_subscribers' => ['boolean'],
        ]);

        $oldStatus = $incident->status;
        $incident->update($validated);

        if ($oldStatus !== $incident->status) {
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'status' => $incident->status,
                'message' => "Status changed from {$oldStatus} to {$incident->status}.",
                'user_id' => Auth::id(),
            ]);

            if ($incident->notify_subscribers) {
                DispatchIncidentNotification::dispatch($incident);
            }
        }

        if ($incident->status === Incident::STATUS_RESOLVED && !$incident->resolved_at) {
            $incident->update(['resolved_at' => now()]);
        }

        return redirect()->route('tenant.incidents.index')
            ->with('success', 'Incident updated successfully.');
    }

    public function destroy(Incident $incident)
    {
        $incident->delete();

        return redirect()->route('tenant.incidents.index')
            ->with('success', 'Incident deleted successfully.');
    }

    public function addUpdate(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'message' => ['required', 'string'],
        ]);

        $update = IncidentUpdate::create([
            'incident_id' => $incident->id,
            'status' => $validated['status'],
            'message' => $validated['message'],
            'user_id' => Auth::id(),
        ]);

        $incident->update(['status' => $validated['status']]);

        if ($validated['status'] === Incident::STATUS_RESOLVED && !$incident->resolved_at) {
            $incident->update(['resolved_at' => now()]);
        }

        if ($incident->notify_subscribers) {
            DispatchIncidentNotification::dispatch($incident);
        }

        return back()->with('success', 'Update added successfully.');
    }
}
