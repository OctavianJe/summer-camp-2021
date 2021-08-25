<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\HomeContactUsType;
use App\Message\ContactUsEmail;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/faq', name: 'faq')]
    public function getFAQ(): Response
    {
        return $this->render('home/faq.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/contact-us', name: 'contact-us')]
    public function contactUs(Request $request, MessageBusInterface $bus): Response
    {
        $user = new User();
        $form = $this->createForm(HomeContactUsType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userName = $form->get('name')->getData();
            $userSubject = $form->get('subject')->getData();
            $userMessage = $form->get('message')->getData();

            $bus->dispatch(new ContactUsEmail($user, $userName, $userSubject, $userMessage));

            $this->addFlash(
                'success',
                "An email was sent! You will be contacted soon!"
            );

            return $this->redirectToRoute('home');
        }

        return $this->render('home/contact_us.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
