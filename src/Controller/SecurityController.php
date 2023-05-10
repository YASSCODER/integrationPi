<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ResetedPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forget-password', name:'forgetten-password')]
    public function forgottenPassword(Request $request, 
    UtilisateurRepository $utilisateurRepository,
    TokenGeneratorInterface $tokenGeneratorInterface,
    EntityManagerInterface $entityManagerInterface,
    MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $utilisateur = $utilisateurRepository->findOneByEmail($form->get('email')->getData());
            if($utilisateur){
                $token = $tokenGeneratorInterface->generateToken();
                $utilisateur->setResetToken($token);
                $entityManagerInterface->persist($utilisateur);
                $entityManagerInterface->flush();

                $url = $this->generateUrl(
                    'reset-password', 
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $context = compact('url', 'utilisateur');

                $email = (new Email())
                ->from('ultrahealth@gmail.com')
                ->to($utilisateur->getEmail())
                ->subject('Reset your password')
                ->text('test email');

                $mailer->send($email);


                return $this->render('email/passwordReset.html.twig',[
                    'utilisateur' => $utilisateur,
                    'url' => $url
                ]);
            }
            $this->addFlash('','hacker danger');
            return $this->redirectToRoute('app_register');

        }
        return $this->render('security/reset_password.html.twig',[
            'requestPassForm' => $form->createView()
        ]);
    }

    #[Route('/forget-password/{token}', name:'reset-password')]
    public function resetPass(
        string $token,
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManagerInterface,
        UserPasswordHasherInterface $userPasswordHasherInterface
    ):Response
    {
        $user = $utilisateurRepository->findOneByResetToken($token);
        if($user){
            $form = $this->createForm(ResetedPasswordType::class);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $user->setResetToken('');
                $user->setPassword(
                    $userPasswordHasherInterface->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();
                return $this->redirectToRoute('app_login');
            }
            return $this->render('security/reseted_password.html.twig',[
                'formPassword' => $form->createView()
            ]);
        }
        $this->addFlash('','invalide');
        return $this->redirectToRoute('app_login');
    }

}
