<?php

namespace App\Listeners;

use App\Mail\WelcomeUser;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRegistrationNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 30;

    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (!$user instanceof User) {
            return;
        }

        // Welcome email to customer
        try {
            Mail::to($user->email)->queue(new WelcomeUser($user));
        } catch (\Throwable $e) {
            Log::error('Welcome email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        // Admin notification — new customer registered
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->queue(new \App\Mail\NewCustomerRegistered($user));
            } catch (\Throwable $e) {
                Log::error('Admin registration email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
