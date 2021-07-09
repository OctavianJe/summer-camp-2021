<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Routing\Annotation\Route;

class MailerService
{
    private MailerInterface $mailer;

    /**
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @Route("/email", name='email')
     * @throws TransportExceptionInterface
     */
    public function sendEmail(User $user, String $password)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($user->getUserIdentifier())
            ->subject('Account password for Who Blocked Me?')
            ->htmlTemplate('mailer/registration_email.html.twig')
            ->context([
                'username' => $user->getUserIdentifier(),
                'password' => $password,
            ]);

        $this->mailer->send($email);
    }

    public function sendBlockerEmail(User $blocker, User $blockee, string $license_plate)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($blocker->getUserIdentifier())
            ->subject('Who Blocked Me?: You blocked somebody!')
            ->htmlTemplate('mailer/blocker_email.html.twig')

            ->context([
                'blockee' => $blockee->getUserIdentifier(),
                'blockee_license_plate' => $license_plate,
            ]);

        $this->mailer->send($email);
    }

    public function sendBlockeeEmail(User $blocker, User $blockee, string $license_plate)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($blocker->getUserIdentifier())
            ->subject('Who Blocked Me?: You are blocked by somebody!')
            ->htmlTemplate('mailer/blockee_email.html.twig')

            ->context([
                'blocker' => $blockee->getUserIdentifier(),
                'blocker_license_plate' => $license_plate,
            ]);

        $this->mailer->send($email);
    }
}
