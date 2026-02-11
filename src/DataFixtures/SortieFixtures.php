<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Utilisation de Faker en français pour générer des données réalistes
        $faker = Factory::create('fr_FR');

        // 1. RÉCUPÉRATION DES DÉPENDANCES
        // Je récupère les données déjà générées par AppFixtures (Campus, Lieux, Participants et États)
        // Cela permet de lier mes sorties à des données existantes.
        $campuses = $manager->getRepository(Campus::class)->findAll();
        $lieux = $manager->getRepository(Lieu::class)->findAll();
        $participants = $manager->getRepository(Participant::class)->findAll();
        $etats = $manager->getRepository(Etat::class)->findAll();

        // Sécurité : Si la base est vide (pas d'AppFixtures lancé), on arrête tout pour éviter le crash.
        if (empty($campuses) || empty($lieux) || empty($participants) || empty($etats)) {
            echo "ATTENTION : Données manquantes. Avez-vous lancé AppFixtures avant ?\n";
            return;
        }

        // 2. PRÉPARATION DES ÉTATS (Mapping Objet)
        // Je dois récupérer les OBJETS 'Etat' correspondants à "Ouverte" et "Terminée".
        // Je compare les libellés de la base de données pour trouver les bons objets.
        $etatOuverte = null;
        $etatTerminee = null;

        foreach ($etats as $etat) {
            if ($etat->getLibelle() === 'Ouverte') {
                $etatOuverte = $etat;
            }
            if ($etat->getLibelle() === 'Terminée') {
                $etatTerminee = $etat;
            }
        }

        // Filet de sécurité : Si l'état exact n'est pas trouvé, j'utilise le premier état disponible.
        if (!$etatOuverte) $etatOuverte = $etats[0];
        if (!$etatTerminee) $etatTerminee = $etats[0];

        // 3. GÉNÉRATION DES 50 SORTIES
        for ($i = 0; $i < 50; $i++) {
            $sortie = new Sortie();

            // --- NOM ALÉATOIRE RÉALISTE ---
            $activites = [
                'Randonnée',
                'Soirée',
                'Bowling',
                'Cinéma',
                'Afterwork',
                'Pique-nique',
                'Concert',
                'Visite',
                'Laser Game',
                'Escape Game'];
            $suffixe = [
                'entre amis',
                'découverte',
                'chez ' . $faker->firstName(),
                'à ' . $faker->city(),
                'nocturne',
                'spécial étudiant'
            ];

            // On combine une activité et un suffixe pour un titre cohérent
            $nomSortie = $faker->randomElement($activites) . ' ' . $faker->randomElement($suffixe);
            $sortie->setNom($nomSortie);

            // Description et infos de base
            $sortie->setInfosSortie($faker->text(200));
            $sortie->setDuree($faker->numberBetween(60, 240)); // Durée en minutes
            $sortie->setNbInscriptionsMax($faker->numberBetween(5, 20));

            // --- LOGIQUE TEMPORELLE ET ÉTAT ---
            // Je simule 50% de sorties passées et 50% de sorties futures pour pouvoir tester mes filtres.
            if ($faker->boolean(50)) {
                // CAS 1 : Sortie Passée (Il y a 1 à 5 mois) -> État "Terminée"
                $dateDebut = $faker->dateTimeBetween('-5 months', '-1 month');
                $etatChoisi = $etatTerminee;
            } else {
                // CAS 2 : Sortie Future (Dans 2 jours à 2 mois) -> État "Ouverte"
                $dateDebut = $faker->dateTimeBetween('+2 days', '+2 months');
                $etatChoisi = $etatOuverte;
            }

            // Conversion DateTime -> DateTimeImmutable (requis par l'entité Sortie)
            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));

            // Calcul de la date limite d'inscription (2 jours avant le début)
            // J'utilise (clone) pour ne pas modifier l'objet $dateDebut original par référence
            $dateLimite = (clone $dateDebut)->modify('-2 days');
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));

            // Affectation de l'état (On passe bien l'objet Entité ici)
            $sortie->setEtat($etatChoisi);

            // --- RELATIONS ---
            // Association aléatoire avec les données récupérées
            $sortie->setCampus($faker->randomElement($campuses));
            $sortie->setLieu($faker->randomElement($lieux));
            $sortie->setOrganisateur($faker->randomElement($participants));

            // --- GESTION DES PARTICIPANTS ---
            // J'ajoute des participants fictifs uniquement si la sortie est Ouverte ou Terminée
            // (Inutile d'ajouter des inscrits sur une sortie "En création" ou "Annulée")
            if ($etatChoisi === $etatOuverte || $etatChoisi === $etatTerminee) {
                // Je sélectionne entre 2 et 5 participants au hasard parmi la liste existante
                $inscrits = $faker->randomElements($participants, $faker->numberBetween(2, 5));
                foreach ($inscrits as $inscrit) {
                    $sortie->addInscrit($inscrit);
                }
            }

            // Persistance de la sortie dans la mémoire de l'EntityManager
            $manager->persist($sortie);
        }

        // Envoi définitif de la requête SQL
        $manager->flush();
    }

    /**
     * Cette méthode permet de définir l'ordre de chargement des fixtures.
     * AppFixtures sera chargé AVANT SortieFixtures pour garantir que les dépendances existent.
     */
    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}
