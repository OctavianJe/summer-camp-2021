<?php


namespace App\Message;


use App\Entity\User;

class RegisterEmail
{
    private $user;
    private $password;

    public function __construct(User $user, String $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}