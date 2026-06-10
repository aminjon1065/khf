<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Double opt-in confirmation email (ТЗ §6.4.3). Queued — sent via the cron-driven queue on shared
 * hosting (D-10).
 */
class SubscriptionConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscriber $subscriber,
        public string $confirmUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Подтверждение подписки — КЧС',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-confirmation',
            with: [
                'confirmUrl' => $this->confirmUrl,
            ],
        );
    }
}
