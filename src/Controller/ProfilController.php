<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ParticipantType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Participant;

final class ProfilController extends AbstractController
{
    #[Route('/profil/modifier', name: 'app_profil_modifier', methods: ['GET', 'POST'])]
    public function modifier(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $participant = $this->getUser();
        assert($participant instanceof Participant);
        $form = $this->createForm(ParticipantType::class, $participant, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $userPasswordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            $entityManagerInterface->persist($participant);
            $entityManagerInterface->flush();
            // LE MESSAGE FLASH : (Type de message, Contenu)
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profil_modifier');
        }
        return $this->render('profil/modifier.html.twig', [
            'form' => $form,
        ]);
    }
}
