<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ConnexionController extends AbstractController
{

    #[Route(path: '/', name: 'app_home')]
    public function home(AuthenticationUtils $authenticationUtils): Response{
        return $this->render('base.html.twig');
    }
    #[Route(path: '/connexion', name: 'app_connexion')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        //todo: remplacer le placeholder 'app_home' par la route de l'"accueil"
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('connexion/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/deconnexion', name: 'app_deconnexion')]
    public function logout(): void
    {
        throw new \LogicException('Déconnexion gérée par Symfony.');
    }
}
