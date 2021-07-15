<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\User;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class ActivityService
{
    /**
     * @var ActivityRepository
     */
    protected $activityRepository;
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->activityRepository = $em->getRepository(Activity::class);
    }

    /**
     * @param string $licensePlate
     * @return array|null
     */
    public function iveBlockedSomebody(string $licensePlate): ?array
    {
        $blockees = $this->activityRepository->findByBlocker($licensePlate);

        if(count($blockees) == 0)
        {
            return null;
        }

        return $blockees;
    }

    /**
     * @param string $licensePlate
     * @return array|null
     */
    public function whoBlockedMe (string $licensePlate): ?array
    {
        $blockers = $this->activityRepository->findByBlockee($licensePlate);

        if(count($blockers) == 0)
        {
            return null;
        }

        return $blockers;
    }

    /**
     * @param User $user
     * @param LicensePlateService $licensePlateService
     * @return array|null
     */
    public function displayLicensePlateBlockers (User $user, LicensePlateService $licensePlateService): ?array
    {
        return $this->activityRepository->findBy(['blockee' => $licensePlateService->getAllLicensePlates($user)]);
    }

    /**
     * @param User $user
     * @param LicensePlateService $licensePlateService
     * @return array|null
     */
    public function displayLicensePlateBlockees (User $user, LicensePlateService $licensePlateService): ?array
    {
        return $this->activityRepository->findBy(['blocker' => $licensePlateService->getAllLicensePlates($user)]);
    }

}