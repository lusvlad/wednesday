<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class Mail extends Mailable
{
    /**
     * Mail constructor
     */
    public function __construct()
    {
        $this->from(config('mail.from'));
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->view($this->view, $this->viewData);
    }
}
