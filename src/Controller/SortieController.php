<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SearchSortieType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class SortieController extends AbstractController
{
    // --- CREATION DE LA ROUTE ACCUEIL --- //
    #[Route('/accueil', name: 'app_accueil')]
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

    // --- CREATION DE LA ROUTE 'Détail d'une sortie' --- //
    #[Route('/sortie/detail/{id}', name: 'app_sortie_detail')]
    public function detail(Sortie $sortie): Response
    {
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }

    // --- CREATION DE LA ROUTE "Créer une sortie" --- //
    #[Route('/sortie/creer', name: 'app_sortie_creer')]
    public function creer(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        // 1. On crée une instance vide
        $sortie = new Sortie();

        // 2. On récupère l'utilisateur connecté (organisateur)
        /** @var Participant $user */
        $user = $this->getUser();
        $sortie->setOrganisateur($user);

        // 3. On définit le campus de la sortie (celui de l'organisateur)
        $sortie->setCampus($user->getCampus());

        // 4 . On crée le formualire
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        //5. Traitemnent du formulaire
        if ($form->isSubmitted() && $form->isValid()) {

            // GESTION DES BOUTONS "ENREGISTRER" vs "PUBLIER"
            // 1. On cherche l'état correspondant dans la base de données
            if ($request->request->has('publier')){
                $etat = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
            } else {
                $etat = $etatRepository->findOneBy(['libelle' => 'En création']);
            }

            // 2. Affichage du résultat
            $sortie->setEtat($etat);

            // 3. Sauvegarde de BDD
            $entityManager->persist($sortie);
            $entityManager->flush();

            // 4. Message pop-up de validation d'action et redirection de l'utilisateur
            $this->addFlash('success', 'La Sortie a bien été ajoutée !');
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('sortie/creer.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
