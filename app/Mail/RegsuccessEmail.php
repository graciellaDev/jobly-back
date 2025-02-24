<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegsuccessEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public array $data; // Данные для передачи в шаблон

    /**
     * Создание нового экземпляра письма.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Построение письма.
     */
    public function build()
    {

         return $this->subject('Регистрация job-ly.ru')->
             view('emails.success', $this->data);



//        return $this->from('your_email@gmail.com', 'Your App Name')
//            ->subject('Пример письма')
//            ->view('emails.success', $this->data)
//         ->with('data', $this->data);

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Регистрация job-ly.ru',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.success',
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
