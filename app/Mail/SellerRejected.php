<?php

namespace App\Mail;

use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Seller $seller, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Seller Application',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.seller-rejected',
        );
    }
}
