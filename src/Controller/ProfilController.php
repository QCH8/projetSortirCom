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
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class ProfilController extends AbstractController
{
    #[Route('/profil/modifier', name: 'app_profil_modifier', methods: ['GET', 'POST'])]
    public function modifier(Request $request, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasher, \Symfony\Bundle\SecurityBundle\Security $security): Response
    {
        /** @var Participant $participant */
        $participant = $this->getUser();
        assert($participant instanceof Participant);

        $originalPseudo = $participant->getPseudo();
        $originalMail = $participant->getMail();

        $form = $this->createForm(ParticipantType::class, $participant, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. GESTION DU MOT DE PASSE
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $userPasswordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            // 2. GESTION DE LA PHOTO DE PROFIL
            $photoFile = $form->get('photo')->getData();
            $deletePhoto = $form->get('delete_photo')->getData(); // On récupère la valeur de la checkbox

            // A. Cas de la suppression
            if ($deletePhoto && $participant->getPhoto()) {
                $oldFilename = $participant->getPhoto();
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/' . $oldFilename;
                
                if (file_exists($oldPath)) {
                    unlink($oldPath); // Suppression physique du fichier
                }
                $participant->setPhoto(null); // Mise à jour en BDD
            }

            // B. Cas de l'upload (si nouveau fichier et pas de suppression demandée simultanément)
            if ($photoFile && !$deletePhoto) {
                // Si une ancienne photo existe, on la supprime avant de mettre la nouvelle
                if ($participant->getPhoto()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/' . $participant->getPhoto();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                        $newFilename
                    );
                    $participant->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            // 3. SAUVEGARDE
            $entityManagerInterface->persist($participant);
            $entityManagerInterface->flush();

            // 4. RE-AUTHENTIFICATION ANTI-DECONNEXION
            // Si l'identifiant (mail ou pseudo) a changé, on reconnecte l'utilisateur pour éviter la perte de session brutale
            if ($originalMail !== $participant->getMail() || $originalPseudo !== $participant->getPseudo()) {
                // On reconnecte l'utilisateur sur le firewall 'main' avec l'authenticator 'form_login'
                $security->login($participant, 'form_login', 'main');
            }

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profil_modifier');
        } elseif ($form->isSubmitted()) {
            $entityManagerInterface->refresh($participant);
        }

        return $this->render('profil/modifier.html.twig', [
            'form' => $form,
            'originalPseudo' => $originalPseudo,
            'originalMail' => $originalMail,
        ]);
    }
}