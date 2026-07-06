<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Http\Request;
use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\Message;

class AdminPasswordResetMailer
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function send(User $user, string $token, Request $request): void
    {
        $fromAddress = (string) config('mail.from.address', 'hello@example.com');
        $fromName = (string) config('mail.from.name', 'Annabel');
        $url = sprintf(
            '%s://%s/admin/reset-password?token=%s',
            $request->scheme(),
            $request->host(),
            rawurlencode($token),
        );

        $this->mailer->send(
            Message::make()
                ->from($fromAddress, $fromName)
                ->to((string) $user->email)
                ->subject('Reset your Annabel CMS password')
                ->text(
                    "We received a request to reset your Annabel CMS admin password.\n\n"
                    . "Open this link to choose a new password:\n{$url}\n\n"
                    . 'This link will expire in 60 minutes.',
                ),
        );
    }
}
