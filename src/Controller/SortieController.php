<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\SortieFilterType;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SortieController extends AbstractController
{
    #[Route('/sorties', name: 'app_sorties')]
    public function index(SortieRepository $sortieRepository, Request $request): Response
    {
        // 1. Récupération de l'utilisateur connecté
        /** @var Participant $utilisateur */
        $utilisateur = $this->getUser();

        /* Sécurité : Redirection si l'utilisateur tente d'accéder à la page sans être connecté
        if (!$utilisateur) {
            return $this->redirectToRoute('app_login'); // Mettre le nom de la route de Quention
        }
        */

        // 2. Préparation du formulaire de filtrage
        // On initialise le formulaire avec le campus de l'utilisateur (exigence de l'énoncé)
        $filtreForm = $this->createForm(SortieFilterType::class, [
            'campus' => $utilisateur->getCampus()
        ]);

        // 3. Analyse de la requête HTTP
        // handleRequest permet de vérifier si l'utilisateur a soumis le formulaire (clic sur "Rechercher")
        $filtreForm->handleRequest($request);

        // 4. Définition des critères de recherche
        // Par défaut, on prend les données initiales du formulaire (le campus de l'user)
        $criteres = $filtreForm->getData();

        // Si le formulaire a été soumis et est valide (ex: dates cohérentes)
        // les données saisies par l'utilisateur écrasent les valeurs par défaut.
        if ($filtreForm->isSubmitted() && $filtreForm->isValid()) {
            $criteres = $filtreForm->getData();
        }

        // 5. Extraction du campus sélectionné pour le Repository
        // On utilise le campus du formulaire ou, à défaut, celui de l'utilisateur
        $campusChoisi = $criteres['campus'] ?? $utilisateur->getCampus();

        // 6. Appel au Repository pour récupérer la liste filtrée
        // On passe l'objet Campus, l'objet Participant (pour les règles de visibilité) et le tableau de filtres
        $listeSorties = $sortieRepository->trouverParCriteres(
            $campusChoisi,
            $utilisateur,
            $criteres
        );

        // 7. Rendu de la page Twig
        return $this->render('sortie/index.html.twig', [
            'sorties'    => $listeSorties,
            'formulaire' => $filtreForm->createView(), // Indispensable pour afficher le formulaire en Twig
        ]);
    }
}
