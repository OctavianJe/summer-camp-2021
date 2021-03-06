<?php

namespace App\Controller;

use App\Entity\LicensePlate;
use App\Form\LicensePlateType;
use App\Repository\LicensePlateRepository;
use App\Service\ActivityService;
use App\Service\LicensePlateService;
use App\Service\MailerService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\UnicodeString;

#[Route('/user/license-plate')]
class LicensePlateController extends AbstractController
{
    #[Route('/', name: 'license-plate/index', methods: ['GET'])]
    public function index(LicensePlateRepository $licensePlateRepository): Response
    {
        return $this->render('license_plate/index.html.twig', [
            'license_plates' => $licensePlateRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/new', name: 'license-plate/new', methods: ['GET', 'POST'])]
    public function new(Request $request, ActivityService $activity, MailerService $mailer, LicensePlateRepository $licensePlateRepository, LicensePlateService $licensePlateService, MessageBusInterface $bus): Response
    {
        $licensePlate = new LicensePlate();
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $licensePlate->setLicensePlate($licensePlateService->normalizeLicensePlate($licensePlate->getLicensePlate()));

            $entry = $licensePlateRepository->findOneBy(['license_plate' => $licensePlate->getLicensePlate()]);

            if($entry && $entry->getUser() == $this->getUser())
            {
                $this->addFlash(
                    'warning',
                    'You already had added this car!'
                );
                return $this->redirectToRoute('license-plate/index');
            }

            $entityManager = $this->getDoctrine()->getManager();

            if($entry and !$entry->getUser())
            {
                $entry->setUser($this->getUser());
                $entityManager->persist($entry);
                $entityManager->flush();

                $blocker = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                if($blocker)
                {
                    foreach ($blocker as &$it)
                    {
                        $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $it->getBlocker()]);

                        if($blockerEntry->getUser())
                        {
                            $mailer->sendBlockeeEmail($blockerEntry->getUser(), $entry->getUser(), $blockerEntry->getLicensePlate());

                            $this->addFlash(
                                'warning',
                                "Your car has been blocked by ".$blockerEntry->getLicensePlate()."!"
                            );

                            $it->setStatus(1);
                            $entityManager->persist($it);
                            $entityManager->flush();
                        }
                    }
                }

                $blockee = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());
                if($blockee)
                {
                    foreach ($blockee as &$it)
                    {
                        $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $it->getBlockee()]);

                        if($blockeeEntry->getUser())
                        {
                            $mailer->sendBlockerEmail($blockeeEntry->getUser(), $entry->getUser(), $blockeeEntry->getLicensePlate());

                            $this->addFlash(
                                'danger',
                                "You blocked the car ".$blockeeEntry->getLicensePlate()."!"
                            );

                            $it->setStatus(1);
                            $entityManager->persist($it);
                            $entityManager->flush();
                        }
                    }
                }

                return $this->redirectToRoute('license-plate/index');
            }

            $licensePlate->setUser($this->getUser());
            $entityManager->persist($licensePlate);
            $entityManager->flush();

            $message = 'The car ' . $licensePlate->getLicensePlate() . ' has been added to your account!';
            $this->addFlash(
                'success',
                $message
            );

            return $this->redirectToRoute('license-plate/index');
        }

        return $this->render('license_plate/new.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license-plate/show', methods: ['GET'])]
    public function show(LicensePlate $licensePlate): Response
    {
        return $this->render('license_plate/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    #[Route('/{id}/edit', name: 'license-plate/edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlate $licensePlate, LicensePlateService $licensePlateService, LicensePlateRepository $licensePlateRepository, ActivityService $activityService): Response
    {
        $oldLicensePlate = $licensePlate->getLicensePlate();

        $message = "Car ".$licensePlate->getLicensePlate()." has been changed to ";

        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($licensePlateService->getIntervalSeconds($licensePlate) < 864000)
            {
                $message = "You have recently updated this car. Try again tomorrow!";
                $this->addFlash(
                    'warning',
                    $message
                );
                return $this->redirectToRoute('license-plate/index');
            }

            $newLicensePlate = $licensePlateService->normalizeLicensePlate($licensePlate);

            if($oldLicensePlate == $newLicensePlate)
            {
                $this->addFlash(
                    'warning',
                    'This license plate already exists!'
                );

                return $this->redirectToRoute('license-plate/index');
            }

            $entry = $licensePlateRepository->findOneBy(['license_plate' => $newLicensePlate, 'user' => $this->getUser()]);
            if($entry)
            {
                $this->addFlash(
                    'warning',
                    'You already had added this car!'
                );
                return $this->redirectToRoute('license-plate/index');
            }

            $blocker = $activityService->iveBlockedSomebody($oldLicensePlate);
            $blockee = $activityService->whoBlockedMe($oldLicensePlate);

            if($blocker != null || $blockee != null)
            {
                $this->addFlash(
                    'warning',
                    "Your license plate can't be changed. It already exists in a report."
                );
                return $this->redirectToRoute('license-plate/index');
            }

            $licensePlate->setLicensePlate($newLicensePlate);

            $this->addFlash(
                'success',
                $message.' '.$licensePlate.'!'
            );

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license-plate/index');
        }

        return $this->render('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license-plate/delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate, ActivityService $activityService): Response
    {
        $oldLicensePlate = $licensePlate->getLicensePlate();

        $blocker = $activityService->iveBlockedSomebody($oldLicensePlate);
        $blockee = $activityService->whoBlockedMe($oldLicensePlate);

        if($blocker != null || $blockee != null)
        {
            $this->addFlash(
                'warning',
                "You can't delete your license plate! A report exists on your number."
            );

            return $this->redirectToRoute('license-plate/index');
        }

        if ($this->isCsrfTokenValid('delete'.$licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();

            $message = 'License plate ' . $licensePlate->getLicensePlate() . ' was successfully deleted!';
            $this->addFlash(
                'success',
                $message
            );
        }

        return $this->redirectToRoute('license-plate/index');
    }
}
