<?php


namespace App\MessageHandler;

use App\Message\ReportEmailNotification;
use App\Service\MailerService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;


class ReportEmailNotificationHandler implements MessageHandlerInterface
{
    private $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function __invoke(ReportEmailNotification $message)
    {
        switch ($message->getType())
        {
            case 'blocker':
                $this->mailerService->sendBlockerEmail($message->getBlocker(), $message->getBlockee(), $message->getLicensePlate());
                break;

            case 'blockee':
                $this->mailerService->sendBlockeeEmail($message->getBlocker(), $message->getBlockee(), $message->getLicensePlate());
                break;
        }
    }
}