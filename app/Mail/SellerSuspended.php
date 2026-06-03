<?php

namespace App\Mail;

use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerSuspended extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Seller $seller, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Seller Account Has Been Suspended',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.seller-suspended',
        );
    }
}
