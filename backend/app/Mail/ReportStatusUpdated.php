<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $toName;
    public $idLaporan;
    public $lokasi;
    public $statusBaru;

    /**
     * Create a new message instance.
     */
    public function __construct($toName, $idLaporan, $lokasi, $statusBaru)
    {
        $this->toName = $toName;
        $this->idLaporan = $idLaporan;
        $this->lokasi = $lokasi;
        $this->statusBaru = $statusBaru;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update Status Laporan #{$this->idLaporan} - PantauJalan Surabaya",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.status_updated',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
