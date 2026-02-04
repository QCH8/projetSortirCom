<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\SearchSortieType;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class SortieController extends AbstractController
{
    #[Route('/', name: 'sortie_liste')]
    public function liste(SortieRepository $sortieRepository, Request $request): Response
    {
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

        return $this->render('sortie/accueil.html.twig', [
            'form' => $form->createView(),
            'sorties' => $sorties,
        ]);
    }
}
