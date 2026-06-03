<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConsultationController extends Controller
{
    public function index()
    {
        return view('pages.consultation');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'product_interest' => ['required', 'string', 'max:200'],
            'q1' => ['required', 'string', 'max:2000'],
            'q1_question' => ['nullable', 'string', 'max:300'],
        ], [
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'product_interest.required' => 'Please select a product.',
            'q1.required' => 'Please answer the question.',
        ]);

        $questionText = $data['q1_question'] ?? 'Your response';
        $isSurvey = str_starts_with($questionText, 'Product Survey:');

        $productLine = "Product: " . $data['product_interest'];
        if ($isSurvey) {
            $lines = array_filter(explode("\n", $data['q1']));
            $formatted = implode("\n", array_map(fn($line) => "  • " . $line, $lines));
            $message = $productLine . "\n\nResponses:\n" . $formatted;
        } else {
            $message = $productLine . "\n\nQ. " . $questionText . "\n  → " . $data['q1'];
        }

        Enquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => 'Free Consultation Request',
            'message' => $message,
            'status' => 'new',
            'is_read' => false,
        ]);

        $storeName = Setting::get('store_name', config('app.name'));
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');

        // Email to owner/admin
        if ($adminEmail) {
            try {
                $body = "New Free Consultation Request\n\n"
                      . "Name: {$data['name']}\n"
                      . "Email: {$data['email']}\n"
                      . "Phone: {$data['phone']}\n\n"
                      . $message;
                Mail::raw($body, function ($m) use ($adminEmail, $data, $storeName) {
                    $m->to($adminEmail)
                      ->subject("[{$storeName}] New Consultation Request — {$data['name']}")
                      ->replyTo($data['email'], $data['name']);
                });
            } catch (\Throwable $e) {
                \Log::error('Consultation admin email failed', ['error' => $e->getMessage()]);
            }
        }

        // Confirmation email to customer
        try {
            $custBody = "Hi {$data['name']},\n\n"
                      . "Thank you for booking a free consultation with {$storeName}. "
                      . "Our specialist will contact you within 24 hours on {$data['phone']}.\n\n"
                      . "Your responses:\n\n" . $message
                      . "\n\nWarm regards,\n{$storeName} Team";
            Mail::raw($custBody, function ($m) use ($data, $storeName) {
                $m->to($data['email'], $data['name'])
                  ->subject("Your consultation request with {$storeName}");
            });
        } catch (\Throwable $e) {
            \Log::error('Consultation customer email failed', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('consultation')
            ->with('success', 'Thank you! Your consultation request has been received. Our specialist will contact you soon.');
    }
}
