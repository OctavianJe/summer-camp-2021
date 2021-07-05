<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MailerController extends AbstractController
{
    /**
     * @Route("/email", name='email')
     */
    public function sendEmail(MailerInterface $mailer, User $user, String $password): Response
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

        $mailer->send($email);

        return $this->redirectToRoute('app_login');
    }
}
