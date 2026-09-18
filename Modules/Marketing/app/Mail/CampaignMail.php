<?php

namespace Modules\Marketing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $emailSubject,
        protected string $htmlBody,
        protected ?string $fromName = null,
        protected ?string $fromEmail = null
    ) {}

    public function envelope(): Envelope
    {
        $from = new Address(
            $this->fromEmail ?? config('mail.from.address'),
            $this->fromName ?? config('mail.from.name')
        );

        return new Envelope(
            from: $from,
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'marketing::emails.campaign',
            with: ['htmlBody' => $this->htmlBody],
        );
    }
}
