<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipationController extends AbstractController
{
    #[Route('/sorties/{id}/desinscription', name: 'participation_desincription', requirements: ['\d+'], methods: ["POST"])]
    public function desinscriptionSortie(
        Sortie $sortie,
        EntityManagerInterface $manager,
        Request $request
    ): RedirectResponse
    {
        //Accès User only
        $this->denyAccessUnlessGranted("ROLE_USER");

        $participant = $this->getUser();
        if(!$participant){
            $this->addFlash("error", "Vous devez être connecté.");
            return $this->redirectToRoute("app_connexion");
        }

        //bien une instance de Participant
        if(!$participant instanceof Participant){
            $this->addFlash("error", "Utilisateur Invalide.");
            return $this->redirectToRoute("app_connexion");
        }

        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('desister'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_sorties");
        }

        //Vérification : la sortie n'a pas déjà eu lieu
        $now = new \DateTimeImmutable("now", "Europe/Paris");

        if($sortie->getDateHeureDebut() <= $now){
            $this->addFlash("error", "Impossible de se désister, la sortie a déjà débuté.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_sorties");
        }

        //Vérification : l'User est bien inscrit
        if(!in_array($participant, $sortie->getInscrits()->toArray(), true)){
            $this->addFlash("error", "Vous n'êtes pas inscrit à cette sortie.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_sorties");
        }

        //Si rien n'est déclenché : on désinscrit
        $sortie->removeInscrit($participant);
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Désinscription prise en compte.");
        return $this->redirectToRoute("app_sorties");
    }

    #[Route('/sorties/{id}/inscription', name: 'participation_incription', requirements: ['\d+'], methods: ["POST"])]
    public function inscriptionSortie(
        Sortie $sortie,
        EntityManagerInterface $manager,
        Request $request,
    ): RedirectResponse
    {
        //Accès User only
        $this->denyAccessUnlessGranted("ROLE_USER");

        $participant = $this->getUser();
        if(!$participant){
            $this->addFlash("error", "Vous devez être connecté.");
            return $this->redirectToRoute("app_connexion");
        }

        //bien une instance de Participant
        if(!$participant instanceof Participant){
            $this->addFlash("error", "Utilisateur Invalide.");
            return $this->redirectToRoute("app_connexion");
        }

        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('inscrire'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_sorties");
        }

        //Vérification : la date limite inscription n'est pas dépassée.
        $now = new \DateTimeImmutable("now", "Europe/Paris");

        if($sortie->getDateLimiteInscription() <= $now){
            $this->addFlash("error", "Impossible de s'inscrire, la date limite est dépassée.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_sorties");
        }

        //Vérification : il reste des places dans la sortie
        if($sortie->getInscrits()->count() >= $sortie->getNbInscriptionsMax()){
            $this->addFlash("error", "Impossible de s'inscrire, limite de participants atteinte.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_sorties");
        }


        //Vérification : le participant n'est pas déjà inscrit
        if($sortie->getInscrits()->contains($participant)){
            $this->addFlash("error", "Impossible de s'inscrire, vous êtes déjà inscrit dans cette sortie.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_sorties");
        }

        //Si rien n'est déclenché : on inscrit
        $sortie->addInscrit($participant);
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Inscription prise en compte.");
        return $this->redirectToRoute("app_sorties");
    }

}
