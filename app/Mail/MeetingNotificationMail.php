<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $email;

    public function __construct($meeting, $email)
    {
        $this->meeting = $meeting;
        $this->email   = $email;
    }

    public function build()
    {
        return $this->subject('Meeting Invitation: ' . $this->meeting->meeting_name)
            ->view('emails.meeting_notification', with([
                'meeting' => $this->meeting,
                'email'   => $this->email
            ]));
    }

}
