<?php

use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\MailManager;

if (!function_exists('mailer')) {
    function mailer(?string $name = null): MailManager|MailerInterface
    {
        /** @var MailManager $manager */
        $manager = app(MailManager::class);

        return $name === null ? $manager : $manager->mailer($name);
    }
}
