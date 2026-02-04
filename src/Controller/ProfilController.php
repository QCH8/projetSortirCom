<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ParticipantType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfilController extends AbstractController
{
    #[Route('/profil/modifier', name: 'app_profil_modifier', methods: ['GET', 'POST'])]
    // #[IsGranted('ROLE_USER')] // À réactiver quand la connexion sera prête
    public function modifier(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $participant = $this->getUser();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /* * TODO: Gestion du mot de passe à implémenter après avoir ajouté 
            * les interfaces UserInterface dans l'entité Participant.
            * $hashedPassword = $userPasswordHasher->hashPassword($participant, $form->get('password')->getData());
            * $participant->setPassword($hashedPassword);
            */

            $entityManagerInterface->persist($participant);
            $entityManagerInterface->flush();
            return $this->redirectToRoute('app_profil_modifier');
        }
        return $this->render('profil/modifier.html.twig', [
            'form' => $form,
        ]);
    }
}
