<?php


namespace App\MessageHandler;


use App\Message\RegisterEmail;
use App\Service\MailerService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RegisterEmailHandler implements MessageHandlerInterface
{
    private $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function __invoke(RegisterEmail $message)
    {
        $this->mailerService->sendRegistrationEmail($message->getUser(), $message->getPassword());
    }
}