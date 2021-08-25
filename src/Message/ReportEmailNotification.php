<?php


namespace App\Message;


use App\Entity\User;

class ReportEmailNotification
{
    private $blocker;
    private $blockee;
    private $license_plate;
    private $type;

    public function __construct(User $blocker, User $blockee, string $license_plate, string $type)
    {
        $this->blocker = $blocker;
        $this->blockee = $blockee;
        $this->license_plate = $license_plate;
        $this->type = $type;
    }

    public function getBlocker(): User
    {
        return $this->blocker;
    }

    public function getBlockee(): User
    {
        return $this->blockee;
    }

    public function getLicensePlate(): string
    {
        return $this->license_plate;
    }

    public function getType(): string
    {
        return $this->type;
    }
}