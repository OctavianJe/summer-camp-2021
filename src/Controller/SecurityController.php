<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MailerService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\UnicodeString;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        return $this->redirectToRoute('app_login');
        //throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher, MailerService $mail): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            //Generate a random password
            $password = substr(sha1(time()), 0, rand(8, 12));

            $user->setPassword($passwordHasher->hashPassword($user, $password));

            $entityManager->persist($user);
            $entityManager->flush();

            $mail->sendEmail($user, $password);
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/password/change', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, SecurityController $security) : Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->add('old_password', PasswordType::class, array('mapped' => false));
        $form->add('new_password', PasswordType::class, array('mapped' => false));
        $form->add('retype_new_password', PasswordType::class, array('mapped' => false));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('old_password')->getData();
            $newPassword = $form->get('new_password')->getData();
            $retypeNewPassword = $form->get('retype_new_password')->getData();

            if($user->getEmail() != $this->getUser()->getUserIdentifier())
            {
                $this->addFlash(
                    'warning',
                    "The email is not the same as the one of the already logged in user. Try again!"
                );
                return $this->redirectToRoute('change_password');
            }

            if(!$passwordHasher->isPasswordValid($this->getUser(), $oldPassword))
            {
                $this->addFlash(
                    'warning',
                    "The old password does not match. Try again"
                );
                return $this->redirectToRoute('change_password');
            }

            if($newPassword != $retypeNewPassword)
            {
                $this->addFlash(
                    'warning',
                    "The new password and its retype are different. Try again"
                );
                return $this->redirectToRoute('change_password');
            }

            if((new UnicodeString($newPassword))->width() < 8)
            {
                $this->addFlash(
                    'warning',
                    "The password is too short. Please use a password that has at least 8 characters!"
                );
                return $this->redirectToRoute('change_password');
            }

            $entityManager = $this->getDoctrine()->getManager();

            $this->getUser()->setPassword($passwordHasher->hashPassword($this->getUser(), $newPassword));

            $entityManager->persist($this->getUser());
            $entityManager->flush();

            $this->addFlash(
                'success',
                "The password has been successfully changed!"
            );

            return $this->redirectToRoute('home');
        }

        return $this->render('security/change_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
