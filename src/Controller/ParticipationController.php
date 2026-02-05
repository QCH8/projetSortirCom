<?php

namespace App\Controller;

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

        $this->addFlash("success", "Désincription prise en compte.");
        return $this->redirectToRoute("app_sorties");
    }



}
