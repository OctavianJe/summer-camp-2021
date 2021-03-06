<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\ActivityBlockeeType;
use App\Form\ActivityBlockerType;
use App\Message\ReportEmailNotification;
use App\Repository\LicensePlateRepository;
use App\Service\ActivityService;
use App\Service\LicensePlateService;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    #[Route('/', name: 'activity/index')]
    public function myCarActivity (Request $request, ActivityService $activityService, LicensePlateService $licensePlateService): Response
    {
        return $this->render('activity/index.html.twig', [
            'activity_blockers' => $activityService->displayLicensePlateBlockees($this->getUser(), $licensePlateService),
            'activity_blockees' => $activityService->displayLicensePlateBlockers($this->getUser(), $licensePlateService),
        ]);
    }

    #[Route('/new/ive_blocked_someone', name: 'activity/ive_blocked_someone', methods: ['GET', 'POST'])]
    public function iveBlockedSomeone(Request $request, LicensePlateRepository $licensePlateRepository, LicensePlateService $licensePlateService, MailerService $mailer, MessageBusInterface $bus): Response
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
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($activity);

                try{
                    $entityManager->flush();
                }catch(\Exception $exception){
                    $this->addFlash(
                        'warning',
                        'This activity report is already registered in our system!'
                    );
                    return $this->redirectToRoute('home');
                }

                if($blockeeEntry->getUser())
                {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlocker()]);

                    $bus->dispatch(new ReportEmailNotification($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate(), 'blocker'));
                    //$mailer->sendBlockerEmail($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate());

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

    #[Route('/new/ive_been_blocked', name: 'activity/ive_been_blocked', methods: ['GET', 'POST'])]
    public function iveBeenBlocked(Request $request, LicensePlateRepository $licensePlateRepository, LicensePlateService $licensePlateService, MailerService $mailer, MessageBusInterface $bus): Response
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
            $activity->setBlocker($licensePlateService->normalizeLicensePlate($activity->getBlocker()));

            $blockerEntry = $licensePlateRepository->findOneBy(['license_plate'=>$activity->getBlocker()]);
            if($blockerEntry)
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($activity);

                try{
                    $entityManager->flush();
                }catch(\Exception $exception){
                    $this->addFlash(
                        'warning',
                        'This activity report is already registered in our system!'
                    );
                    return $this->redirectToRoute('home');
                }

                if($blockerEntry->getUser()) {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlockee()]);

                    $bus->dispatch(new ReportEmailNotification($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate(), 'blockee'));
                    //$mailer->sendBlockerEmail($blockeeEntry->getUser(), $blockerEntry->getUser(), $blockeeEntry->getLicensePlate());

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

    #[Route('/{blocker}', name: 'activity/delete')]
    public function solveActivity (Request $request, Activity $activity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activity->getBlocker(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $activity->setStatus(3);
            $entityManager->flush();;

            $message = 'The activity was marked as solved!';
            $this->addFlash(
                'success',
                $message
            );
        }

        return $this->redirectToRoute('activity/index');
    }
}