<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipationController extends AbstractController
{
    #[Route('/sortie/{id}/desinscription', name: 'participation_desinscription', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

        //todo: décommenter une fois la mise en place du token CSRF sur les pages twig
        /*
        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('desister'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_accueil");
        }
        */

        //Vérification : la sortie n'a pas déjà eu lieu
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        if($sortie->getDateHeureDebut() <= $now){
            $this->addFlash("error", "Impossible de se désister, la sortie a déjà débuté.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Vérification : l'User est bien inscrit
        if(!in_array($participant, $sortie->getInscrits()->toArray(), true)){
            $this->addFlash("error", "Vous n'êtes pas inscrit à cette sortie.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Si rien n'est déclenché : on désinscrit
        $sortie->removeInscrit($participant);
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Désinscription prise en compte.");
        return $this->redirectToRoute("app_accueil");
    }

    #[Route('/sortie/{id}/inscription', name: 'participation_inscription', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

        //todo: décommenter une fois la mise en place du token CSRF sur les pages twig
        /*
        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('inscrire'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_accueil");
        }
        */

        //Vérification : la date limite inscription n'est pas dépassée.
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        if($sortie->getDateLimiteInscription() <= $now){
            $this->addFlash("error", "Impossible de s'inscrire, la date limite est dépassée.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Vérification : il reste des places dans la sortie
        if($sortie->getInscrits()->count() >= $sortie->getNbInscriptionsMax()){
            $this->addFlash("error", "Impossible de s'inscrire, limite de participants atteinte.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Vérification : le participant n'est pas déjà inscrit
        if($sortie->getInscrits()->contains($participant)){
            $this->addFlash("error", "Impossible de s'inscrire, vous êtes déjà inscrit dans cette sortie.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Si rien n'est déclenché : on inscrit
        $sortie->addInscrit($participant);
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Inscription prise en compte.");
        return $this->redirectToRoute("app_accueil");
    }

    #[Route('/sortie/{id}/publication', name: 'participation_publication', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function publicationSortie(
        Sortie $sortie,
        EtatRepository $etatRepo,
        EntityManagerInterface $manager,
        Request $request
    ): RedirectResponse
    {
        //Accès User only
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
        //bien l'organisateur de l'event
        if($participant !== $sortie->getOrganisateur()){
            $this->addFlash("error", "Vous n'êtes pas l'organisateur.");
            return $this->redirectToRoute("app_accueil");
        }
        //todo: décommenter une fois la mise en place du token CSRF sur les pages twig
        /*
        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('inscrire'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_accueil");
        }
        */

        //Vérification : la date limite inscription n'est pas dépassée.
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        if($sortie->getDateLimiteInscription() <= $now){
            $this->addFlash("error", "Pas de publication d'événements dans le passé.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }
        //Vérification pas déjà publiée
        if($sortie->getEtat() === $etatRepo->findOneBy(['libelle' => 'Ouverte'])){
            $this->addFlash("error", "Sortie déjà publiée");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Si rien n'est déclenché : on publie
        $sortie->setEtat($etatRepo->findOneBy(['libelle' => 'Ouverte']));
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Sortie publiée");
        return $this->redirectToRoute("app_accueil");
    }

    #[Route('/sortie/{id}/annulation', name: 'participation_annulation', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function annulationSortie(
        Sortie $sortie,
        EtatRepository $etatRepo,
        EntityManagerInterface $manager,
        Request $request
    ): RedirectResponse
    {
        //Accès User only
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
        //bien l'organisateur de l'event
        if($participant !== $sortie->getOrganisateur()){
            $this->addFlash("error", "Vous n'êtes pas l'organisateur.");
            return $this->redirectToRoute("app_accueil");
        }
        //todo: décommenter une fois la mise en place du token CSRF sur les pages twig
        /*
        //Protection vs CSRF
        //todo: verif génération du token
        if(!$this->isCsrfTokenValid('inscrire'.$sortie->getId(), (string) $request->request->get('_token')))
        {
            $this->addFlash("error", "Action non autorisée (CSRF).");
            return $this->redirectToRoute("app_accueil");
        }
        */

        //Vérification : l'événement n'a pas commencé.
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        if($sortie->getDateHeureDebut() < $now){
            $this->addFlash("error", "Pas d'annulation d'événements déjà commencé.");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }
        //Vérification pas déjà annulée
        if($sortie->getEtat() === $etatRepo->findOneBy(['libelle' => 'Annulée'])){
            $this->addFlash("error", "Sortie déjà annulée");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Bien publiée
        if(!($sortie->getEtat() === $etatRepo->findOneBy(['libelle' => 'Ouverte']))){
            $this->addFlash("error", "Sortie non publiée");
            //todo: changement de redirect vers la page détail de la sortie
            return $this->redirectToRoute("app_accueil");
        }

        //Si rien n'est déclenché : on annule
        $sortie->setEtat($etatRepo->findOneBy(['libelle' => 'Annulée']));
        //todo: affichage twig d'un message d'annulation ? || ajout d'une colonne de message dans Sorties
        $manager->persist($sortie);
        $manager->flush();

        $this->addFlash("success", "Sortie annulée");
        return $this->redirectToRoute("app_accueil");
    }
}
