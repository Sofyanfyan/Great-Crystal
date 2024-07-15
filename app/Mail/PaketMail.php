<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaketMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $mailData;
    public $subject;
    public $pdf;
    public $pdfReport;

    public function __construct($mailData, $subject, $pdf, $pdfReport = null)
    {
        $this->mailData = $mailData;
        $this->subject = $subject;
        $this->pdf = $pdf;
        $this->pdfReport = $pdfReport;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.paket-mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    // public function attachments(): array
    // {
    //     $file = [
    //         Attachment::fromData(fn () => $this->pdf->output(), 'Paket '.date('F Y', strtotime($this->mailData['bill'][0]->created_at)). ' ' . $this->mailData['student']->name)
    //         ->withMime('application/pdf'),
    //     ];

    //     if($this->pdfReport)
    //     {
    //         array_push($file, 
    //         Attachment::fromData(fn () => $this->pdfReport->output(), 'Report '. $this->mailData['bill'][0]->type. ' ' .date('F Y'). ' ' . $this->mailData['student']->name)
    //         ->withMime('application/pdf'));
    //     }

    //     return $file;
    // }

    public function build()
    {
        $email = $this->subject($this->subject)
            ->view('emails.paket-mail')
            ->attachData($this->pdf->output(), 'Paket ' . date('F Y', strtotime($this->mailData['bill'][0]->created_at)) . ' ' . $this->mailData['student']->name . '.pdf', [
                'mime' => 'application/pdf',
            ]);

        if ($this->pdfReport) {
            $email->attachData($this->pdfReport->output(), 'Report ' . $this->mailData['bill'][0]->type . ' ' . date('F Y') . ' ' . $this->mailData['student']->name . '.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }
}
