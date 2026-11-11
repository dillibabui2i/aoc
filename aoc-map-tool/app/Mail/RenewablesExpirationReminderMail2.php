<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RenewablesExpirationReminderMail2 extends Mailable
{
    use Queueable, SerializesModels;

    public $renewable;
    public $days;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($renewable, $days)
    {
        $this->renewable = $renewable;
        $this->days = $days;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Upcoming Expiry of '.$this->renewable->renewablename.' – Action Required';
        return $this->subject($subject)->view('renewableemail.RenewablesExpirationReminderMail2');
    }
}
