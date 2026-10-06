<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A Rodeo email template sent as a test, already filled in for its recipient. */
class TemplateTest extends Mailable
{
    public function __construct(public string $subjectLine, public string $bodyHtml) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->bodyHtml);
    }
}
