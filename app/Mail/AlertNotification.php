<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Emergency-alert email to a subscriber (ТЗ §6.4.3). Title/body are pre-resolved to the
 * subscriber's locale by the dispatch job. Queued — sent via the cron-driven queue (D-10).
 */
class AlertNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public string $levelLabel,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.$this->levelLabel.'] '.$this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.alert',
            with: [
                'title' => $this->title,
                'body' => $this->body,
                'levelLabel' => $this->levelLabel,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
