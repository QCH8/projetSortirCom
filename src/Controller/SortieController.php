<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SearchSortieType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Services\MiseAJourEtatSortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SortieController extends AbstractController
{
    // --- CREATION DE LA ROUTE ACCUEIL --- //
    #[Route('/accueil', name: 'app_accueil')]
    public function liste(SortieRepository $sortieRepository, Request $request, MiseAJourEtatSortie $majEtatSortie): Response
    {
        //Accès User only
        $participant = $this->getUser();
        if (!$participant) {
            $this->addFlash("error", "Vous devez être connecté.");
            return $this->redirectToRoute("app_connexion");
        }
        //bien une instance de Participant
        if (!$participant instanceof Participant) {
            $this->addFlash("error", "Utilisateur Invalide.");
            return $this->redirectToRoute("app_connexion");
        }

        // 1. Récupération de l'utilisateur connecté (le Participant)
        /** @var Participant $utilisateur */
        $utilisateur = $this->getUser();

        // 2. Préparation des données initiales (Campus par défaut de l'utilisateur)
        $donneesRecherche = [
            'campus' => $utilisateur->getCampus()
        ];

        // 3. Création et gestion du formulaire
        $form = $this->createForm(SearchSortieType::class, $donneesRecherche);
        $form->handleRequest($request);

        // 4. Récupération des critères (soit ceux par défaut, soit ceux saisis par l'utilisateur)
        $criteres = $form->getData();

        // 5. Appel de la méthode personnalisée dans le Repository
        $sorties = $sortieRepository->findSearch($utilisateur, $criteres);

        //6. Mise à jour des états à l'aide du Service MiseAJourEtatSortie
        $majEtatSortie->synchroniserSiBesoinListe($sorties);

        return $this->render('sortie/accueil.html.twig', [
            'form' => $form->createView(),
            'sorties' => $sorties,
        ]);
    }

    // --- CREATION DE LA ROUTE 'Détail d'une sortie' --- //
    #[Route('/sortie/detail/{id}', name: 'app_sortie_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(Sortie $sortie, MiseAJourEtatSortie $majEtatSortie): Response
    {

        if ($sortie->isHistorisee()) {
            throw $this->createAccessDeniedException("Cette sortie est historisée et n'est plus consultable.");
        }

        //Accès User only
        $participant = $this->getUser();
        if (!$participant) {
            $this->addFlash("error", "Vous devez être connecté.");
            return $this->redirectToRoute("app_connexion");
        }
        //bien une instance de Participant
        if (!$participant instanceof Participant) {
            $this->addFlash("error", "Utilisateur Invalide.");
            return $this->redirectToRoute("app_connexion");
        }

        //Mise à jour pour cette sortie si besoin
        $majEtatSortie->synchroniserSiBesoin($sortie);

        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }

    // --- CREATION DE LA ROUTE "Créer une sortie" --- //
    #[Route('/sortie/creer', name: 'app_sortie_creer', methods: ['GET', 'POST'])]
    public function creer(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        // 1. Vérification que l'utilisateur est connecté
        /** @var Participant $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour créer une sortie.');
            return $this->redirectToRoute("app_connexion");
        }

        $sortie = new Sortie();
        // L'organisateur est l'utilisateur connecté
        $sortie->setOrganisateur($user);
        // Le campus est celui de l'organisateur
        $sortie->setCampus($user->getCampus());

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 2. Gestion des boutons : "Publier" ou "Enregistrer"
            if ($request->request->has('publier')) {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                $message = 'Votre sortie a été publiée !';
            } else {
                // Sinon, c'est le bouton "Enregistrer" (Brouillon)
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
                $message = 'Votre sortie a été enregistrée en brouillon.';
            }

            if ($etat) {
                $sortie->setEtat($etat);

                $entityManager->persist($sortie);
                $entityManager->flush();

                $this->addFlash('success', $message);
                return $this->redirectToRoute('app_accueil');
            } else {
                $this->addFlash('error', 'Erreur : État introuvable en base de données.');
            }
        }

        return $this->render('sortie/creer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // --- CREATION DE LA ROUTE "Modifier une sortie" --- //
    #[Route('/sortie/modifier/{id}', name: 'app_sortie_modifier', requirements: ['id' => '\d+'])]
    public function modifier(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository
    ): Response
    {
        // 1. Sécurité : Vérifier que l'utilisateur est bien l'organisateur
        if ($this->getUser() !== $sortie->getOrganisateur()) {
            $this->addFlash('error', 'Vous n\'êtes pas l\'organisateur de cette sortie.');
            return $this->redirectToRoute('app_accueil');
        }

        // 2. Sécurité : Vérifier que la sortie est bien en "En création"
        if ($sortie->getEtat()->getLibelle() !== 'En création') {
            $this->addFlash('error', 'Cette sortie est déjà publiée, vous ne pouvez plus la modifier.');
            return $this->redirectToRoute('app_accueil');
        }

        // 3. Je récupère la ville du lieu actuel pour l'envoyer au formulaire
        $villeActuelle = $sortie->getLieu() ? $sortie->getLieu()->getVille() : null;

        // 4. Création du formulaire avec l'option personnalisée 'ville_auto'
        $form = $this->createForm(SortieType::class, $sortie, [
            'ville_auto' => $villeActuelle
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($request->request->has('publier')) {
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                $message = 'Votre sortie a été modifiée et publiée !';
            } else {
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
                $message = 'Modifications enregistrées (toujours en brouillon).';
            }

            if ($etat) {
                $sortie->setEtat($etat);
                $entityManager->flush();

                $this->addFlash('success', $message);
                return $this->redirectToRoute('app_accueil');
            }
        }

        return $this->render('sortie/modifier.html.twig', [
            'form' => $form->createView(),
            'sortie' => $sortie
        ]);
    }

    // --- CREATION DE LA ROUTE "Supprimer une sortie" --- //
    #[Route('/sortie/supprimer/{id}', name: 'app_sortie_supprimer', requirements: ['id' => '\d+'])]
    public function supprimer(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $sortie->getOrganisateur()) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_accueil');
        }

        if ($sortie->getEtat()->getLibelle() !== 'En création') {
            $this->addFlash('error', 'Impossible de supprimer une sortie publiée.');
            return $this->redirectToRoute('app_accueil');
        }

        $entityManager->remove($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été supprimée définitivement.');
        return $this->redirectToRoute('app_accueil');
    }

    // --- ROUTES API --- //
    #[Route('/lieu/api/{id}', name: 'app_lieu_api', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getLieuApi(\App\Entity\Lieu $lieu): Response
    {
        return $this->json([
            'rue' => $lieu->getRue(),
            'codePostal' => $lieu->getVille()->getCodePostal(),
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude(),
        ]);
    }

    #[Route('/ville/api/{id}/lieux', name: 'app_ville_lieux_api', methods: ['GET'])]
    public function getLieuxParVille(\App\Entity\Ville $ville): Response
    {
        $lieux = $ville->getLieux();
        $data = [];

        foreach ($lieux as $lieu) {
            $data[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom()
            ];
        }

        return $this->json($data);
    }
}
