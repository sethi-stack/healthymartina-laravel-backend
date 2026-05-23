<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Mail;

class ExternalMailService
{
    public function sendPdf(
        string $toEmail,
        string $subject,
        string $bodyText,
        string $fileName,
        string $pdfBinary
    ): void {
        Mail::raw($bodyText, function ($message) use ($toEmail, $subject, $fileName, $pdfBinary) {
            $message->to($toEmail)
                ->subject($subject)
                ->attachData($pdfBinary, $fileName);
        });
    }

    public function sendDeliveryNotice(
        string $toEmail,
        string $subject,
        string $bodyText,
        string $fileName,
        string $pdfBinary
    ): void {
        Mail::raw($bodyText, function ($message) use ($toEmail, $subject, $fileName, $pdfBinary) {
            $message->to($toEmail)
                ->subject($subject)
                ->attachData($pdfBinary, $fileName);
        });
    }
}

