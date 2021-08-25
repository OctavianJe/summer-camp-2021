<?php


namespace App\MessageHandler;


use App\Message\ContactUsEmail;
use App\Service\MailerService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ContactUsEmailHandler implements MessageHandlerInterface
{
    private $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function __invoke(ContactUsEmail $message)
    {
        $this->mailerService->sendContactUsEmail($message->getUser(), $message->getUserName(), $message->getUserSubject(), $message->getUserSubject());
    }
}