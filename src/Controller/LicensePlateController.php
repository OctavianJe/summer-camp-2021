<?php

namespace App\Controller;

use App\Entity\LicensePlate;
use App\Form\LicensePlateType;
use App\Repository\LicensePlateRepository;
use App\Service\ActivityService;
use App\Service\MailerService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/license/plate')]
class LicensePlateController extends AbstractController
{
    #[Route('/', name: 'license_plate_index', methods: ['GET'])]
    public function index(LicensePlateRepository $licensePlateRepository): Response
    {
        return $this->render('license_plate/index.html.twig', [
            'license_plates' => $licensePlateRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/new', name: 'license_plate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ActivityService $activity, MailerService $mailer, LicensePlateRepository $licensePlateRepository): Response
    {
        $licensePlate = new LicensePlate();
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entry = $licensePlateRepository->findOneBy(['license_plate' => $licensePlate->getLicensePlate()]);

            $entityManager = $this->getDoctrine()->getManager();

            if($entry and !$entry->getUser())
            {
                $entry->setUser($this->getUser());
                $entityManager->persist($entry);
                $entityManager->flush();

                $blocker = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                if($blocker)
                {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $blocker]);
                    $mailer->sendBlockeeEmail($blockerEntry->getUser(), $entry->getUser(), $blockerEntry->getLicensePlate());
                }

                $blockee = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());
                if($blockee)
                {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockee]);
                    $mailer->sendBlockerEmail($blockeeEntry->getUser(), $entry->getUser(), $blockeeEntry->getLicensePlate());
                }
                return $this->redirectToRoute('license_plate_index');
            }

            $licensePlate->setUser($this->getUser());
            $entityManager->persist($licensePlate);
            $entityManager->flush();

            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/new.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plate_show', methods: ['GET'])]
    public function show(LicensePlate $licensePlate): Response
    {
        return $this->render('license_plate/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    #[Route('/{id}/edit', name: 'license_plate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlate $licensePlate): Response
    {
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate): Response
    {
        if ($this->isCsrfTokenValid('delete'.$licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('license_plate_index');
    }
}
