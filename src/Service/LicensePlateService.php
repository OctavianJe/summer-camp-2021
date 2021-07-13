<?php


namespace App\Service;

use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
use Doctrine\ORM\EntityManagerInterface;


class LicensePlateService
{
    /**
     * @var LicensePlateRepository
     */
    protected $licensePlateRepository;
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->licensePlateRepository = $em->getRepository(LicensePlate::class);
    }

    /**
     * @param LicensePlate $licensePlate
     * @return string
     */
    public function normalizeLicensePlate(string $licensePlate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $licensePlate));
    }

    /**
     * @param User $user
     * @return int
     */
    public function countLicensePlates(User $user): int
    {
        return count($this->licensePlateRepository->findBy(['user' => $user]));
    }
}