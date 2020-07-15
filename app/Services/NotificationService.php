<?php

namespace App\Services;

use App\Mail\Mail;
use Illuminate\Contracts\Mail\Mailer;

class NotificationService
{
    private Mailer $mailer;

    /**
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param string $emailTo
     * @param string $code
     */
    public function code(string $emailTo, string $code): void
    {
        // best practice is move to queue
        $mail = new Mail();

        $mail->to($emailTo)
            ->subject('Проверочный код')
            ->html($code);
        $this
            ->mailer
            ->send($mail);
    }
}
