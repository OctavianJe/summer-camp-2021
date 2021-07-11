<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\ActivityBlockeeType;
use App\Form\ActivityBlockerType;
use App\Repository\ActivityRepository;
use App\Repository\LicensePlateRepository;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\UnicodeString;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/ive_blocked_someone_new', name: 'ive_blocked_someone_new', methods: ['GET', 'POST'])]
    public function iveBlockedSomeone(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer): Response
    {
        $activity = new Activity();

        $licensePlateNumber = count($this->getUser()->getLicensePlates());
        if($licensePlateNumber == 1)
        {
            $form = $this->createForm(ActivityBlockerType::class, $activity,[
                'oneCar' => true,
                'multipleCars' => false,
            ]);
        }
        elseif($licensePlateNumber > 1)
        {
            $form = $this->createForm(ActivityBlockerType::class, $activity,[
                'oneCar' => false,
                'multipleCars' => true,
            ]);
        }
        else
        {
            $this->addFlash("danger", 'Add a new car to continue.');
            return $this->redirectToRoute('home');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setBlockee((new UnicodeString($activity->getBlockee()))->camel()->upper());
            $activity->setBlocker((new UnicodeString($activity->getBlocker()))->camel()->upper());
            $entityManager->persist($activity);
            $entityManager->flush();

            $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate'=>$activity->getBlockee()]);
            if($blockeeEntry)
            {
                $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlocker()]);
                $mailer->sendBlockeeEmail($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate());

                $message = "The owner of the car ".$activity->getBlockee()." has been emailed!";
                $this->addFlash(
                    'success',
                    $message
                );
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

    #[Route('/ive_been_blocked_new', name: 'ive_been_blocked_new', methods: ['GET', 'POST'])]
    public function iveBeenBlocked(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer): Response
    {
        $activity = new Activity();

        $licensePlateNumber = count($this->getUser()->getLicensePlates());

        if($licensePlateNumber == 1)
        {
            $form = $this->createForm(ActivityBlockeeType::class, $activity,[
                'oneCar' => true,
                'multipleCars' => false,
            ]);
        }
        elseif($licensePlateNumber > 1)
        {
            $form = $this->createForm(ActivityBlockeeType::class, $activity,[
                'oneCar' => false,
                'multipleCars' => true,
            ]);
        }
        else
        {
            $this->addFlash("danger", 'Add a new car to continue.');
            return $this->redirectToRoute('home');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setBlockee((new UnicodeString($activity->getBlockee()))->camel()->upper());
            $activity->setBlocker((new UnicodeString($activity->getBlocker()))->camel()->upper());
            $entityManager->persist($activity);
            $entityManager->flush();

            $blockerEntry = $licensePlateRepository->findOneBy(['license_plate'=>$activity->getBlocker()]);
            if($blockerEntry)
            {
                $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlockee()]);
                $mailer->sendBlockerEmail($blockeeEntry->getUser(), $blockerEntry->getUser(), $blockeeEntry->getLicensePlate());

                $message = "The owner of the car ".$activity->getBlocker()." has been emailed!";
                $this->addFlash(
                    'success',
                    $message
                );
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
