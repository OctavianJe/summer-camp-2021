<?php


namespace App\Message;


use App\Entity\User;

class ContactUsEmail
{
    private $user;
    private $userName;
    private $userSubject;
    private $userMessage;

    public function __construct(User $user, string $userName, string $userSubject, string $userMessage)
    {
        $this->user = $user;
        $this->userName = $userName;
        $this->userSubject = $userSubject;
        $this->userMessage = $userMessage;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getUserSubject(): string
    {
        return $this->userSubject;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}