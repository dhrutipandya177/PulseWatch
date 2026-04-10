<?php

declare(strict_types=1);

use App\Models\User;

return [
    'model' => User::class,

    'key' => 'stripe_id',

    'driver' => null,

    'currency' => 'usd',

    'currency_locale' => 'en_US',

    'tax' => ['percent' => 0],

    'withholding_tax' => ['percent' => 0],

    'confirm_payment' => true,

    'payment_notification' => \App\Notifications\Subscription\PaymentFailed::class,

    'trial_days' => 14,

    'guess_from_url' => false,
];
