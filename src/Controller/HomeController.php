<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\ActivityBlockeeType;
use App\Form\ActivityBlockerType;
use App\Repository\ActivityRepository;
use App\Repository\LicensePlateRepository;
use App\Service\LicensePlateService;
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

    #[Route('/faq', name: 'faq')]
    public function getFAQ(): Response
    {
        return $this->render('home/faq.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/contact-us', name: 'contact-us')]
    public function contactUs(): Response
    {
        return $this->render('home/contact_us.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
