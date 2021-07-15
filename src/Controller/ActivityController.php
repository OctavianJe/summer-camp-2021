<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\ActivityBlockeeType;
use App\Form\ActivityBlockerType;
use App\Repository\LicensePlateRepository;
use App\Service\LicensePlateService;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    #[Route('/ive_blocked_someone', name: 'ive_blocked_someone', methods: ['GET', 'POST'])]
    public function iveBlockedSomeone(Request $request, LicensePlateRepository $licensePlateRepository, LicensePlateService $licensePlateService, MailerService $mailer): Response
    {
        $activity = new Activity();

        $licensePlateNumber = count($this->getUser()->getLicensePlates());
        if($licensePlateNumber == 1)
        {
            $form = $this->createForm(ActivityBlockerType::class, $activity,[
                'oneCar' => true,
            ]);
        }
        else
        {
            $form = $this->createForm(ActivityBlockerType::class, $activity,[
                'oneCar' => false,
            ]);
        }

        $firstLicensePlate = $licensePlateRepository->findOneBy(['user' => $this->getUser()]);
        if($firstLicensePlate)
        {
            $activity->setBlocker($firstLicensePlate);
        }
        else
        {
            $this->addFlash("danger", 'Add a new car to continue.');
            return $this->redirectToRoute('home');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setBlockee($licensePlateService->normalizeLicensePlate($activity->getBlockee()));

            $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate'=>$activity->getBlockee()]);

            if($blockeeEntry)
            {
                if($blockeeEntry->getUser())
                {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlocker()]);

                    $mailer->sendBlockerEmail($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate());

                    $this->addFlash(
                        'info',
                        "The owner of the car ".$activity->getBlockee()." has been emailed!"
                    );

                    $activity->setStatus(1);
                }
                else
                {
                    $this->addFlash(
                        'warning',
                        'Your report was register but the blocker does not have an account! They will be contacted as soon as they are registered!'
                    );
                }
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlockee());
                $entityManager->persist($licensePlate);
                $entityManager->flush();

                $message = "The owner of the car ".$activity->getBlockee()." is not registered! They will be contacted as soon as they are registered!";
                $this->addFlash(
                    'warning',
                    $message
                );
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('activity/blocker_new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);

    }

    #[Route('/ive_been_blocked', name: 'ive_been_blocked', methods: ['GET', 'POST'])]
    public function iveBeenBlocked(Request $request, LicensePlateRepository $licensePlateRepository, LicensePlateService $licensePlateService, MailerService $mailer): Response
    {
        $activity = new Activity();

        $licensePlateNumber = count($this->getUser()->getLicensePlates());

        if($licensePlateNumber == 1)
        {
            $form = $this->createForm(ActivityBlockeeType::class, $activity,[
                'oneCar' => true,
            ]);
        }
        else
        {
            $form = $this->createForm(ActivityBlockeeType::class, $activity,[
                'oneCar' => false,
            ]);
        }

        $firstLicensePlate = $licensePlateRepository->findOneBy(['user' => $this->getUser()]);
        if($firstLicensePlate)
        {
            $activity->setBlockee($firstLicensePlate);
        }
        else
        {
            $this->addFlash("danger", 'Add a new car to continue.');
            return $this->redirectToRoute('home');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setBlockee($licensePlateService->normalizeLicensePlate($activity->getBlockee()));
            $activity->setBlocker($licensePlateService->normalizeLicensePlate($activity->getBlocker()));

            $entityManager->persist($activity);
            $entityManager->flush();

            $blockerEntry = $licensePlateRepository->findOneBy(['license_plate'=>$activity->getBlocker()]);
            if($blockerEntry)
            {
                if($blockerEntry->getUser()) {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlockee()]);

                    $mailer->sendBlockerEmail($blockeeEntry->getUser(), $blockerEntry->getUser(), $blockeeEntry->getLicensePlate());

                    $this->addFlash(
                        'info',
                        "The owner of the car ".$activity->getBlocker()." has been emailed!"
                    );

                    $activity->setStatus(1);
                }
                else{
                    $this->addFlash(
                        'warning',
                        'Your report was register but the blockee does not have an account! They will be contacted as soon as they are registered!'
                    );
                }
            }
            else
            {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlocker());
                $entityManager->persist($licensePlate);
                $entityManager->flush();

                $message = "The owner of the car ".$activity->getBlocker()." is not registered! They will be contacted as soon as they are registered!";
                $this->addFlash(
                    'warning',
                    $message
                );
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('activity/blockee_new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);

    }
    
}