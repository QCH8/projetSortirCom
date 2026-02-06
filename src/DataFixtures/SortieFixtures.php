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
        // Utilisation de Faker en français
        $faker = Factory::create('fr_FR');

        // 1. RÉCUPÉRATION DES DÉPENDANCES
        // Je récupère les données déjà générées par les autres fixtures (jsuqu'ici : campus, lieux, participants et états)
        $campuses = $manager->getRepository(Campus::class)->findAll();
        $lieux = $manager->getRepository(Lieu::class)->findAll();
        $participants = $manager->getRepository(Participant::class)->findAll();
        $etats = $manager->getRepository(Etat::class)->findAll();

        // Petite sécurité : si aucune donnée dans la base, j'arrête tout pour éviter le crash
        if (empty($campuses) || empty($lieux) || empty($participants) || empty($etats)) {
            echo "ATTENTION : Pas de données (Lieux/Campus/Participants) trouvées. Je ne peux pas créer de sorties.\n";
            return;
        }

        // Je trie les états dans des variables pour pouvoir les attribuer facilement plus bas
        $etatOuverte = null;
        $etatPassee = null;
        $etatCreee = null;

        foreach ($etats as $etat) {
            if ($etat->getLibelle() === 'Ouverte') $etatOuverte = $etat;
            if ($etat->getLibelle() === 'Passée') $etatPassee = $etat;
            if ($etat->getLibelle() === 'En création') $etatCreee = $etat;
        }

        // 2. CRÉATION DES SORTIES
        // Création de 50 sorties avec des cas différents (passées, futures, etc.)

        for ($i = 0; $i < 50; $i++) {
            $sortie = new Sortie();

            // Données basiques aléatoires
            $sortie->setNom("Sortie " . $faker->city());
            $sortie->setInfosSortie($faker->paragraph(2));
            $sortie->setDuree($faker->numberBetween(60, 240)); // Durée entre 1h et 4h
            $sortie->setNbInscriptionsMax($faker->numberBetween(5, 20));

            // LOGIQUE DES DATES ET ÉTATS
            // Je décide aléatoirement si c'est une vieille sortie (Passée) ou une à venir (Ouverte)
            if ($faker->boolean(50)) {
                // CAS 1 : Sortie Passée (Il y a entre 1 et 5 mois)
                $dateDebut = $faker->dateTimeBetween('-5 months', '-1 month');
                $etatChoisi = $etatPassee;
            } else {
                // CAS 2 : Sortie Future (Dans les 2 prochains mois)
                $dateDebut = $faker->dateTimeBetween('+2 days', '+2 months');
                $etatChoisi = $etatOuverte;
            }

            // Conversion en immutable (demande entité)
            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));

            // Je calcule la date limite pour qu'elle soit toujours 2 jours avant le début de la sortie
            // Ajout du clône pour ne pas modifier la date de début (merci coach québécois)
            $dateLimite = (clone $dateDebut)->modify('-2 days');
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));

            // Affectation de l'état calculé
            if ($etatChoisi) {
                $sortie->setEtat($etatChoisi);
            }

            // RELATIONS
            // Je pioche au hasard dans les listes récupérées plus haut
            $sortie->setCampus($faker->randomElement($campuses));
            $sortie->setLieu($faker->randomElement($lieux));
            $sortie->setOrganisateur($faker->randomElement($participants));

            // GESTION DES INSCRITS
            // Si la sortie est ouverte ou passée, j'ajoute quelques participants fictifs pour tester l'affichage
            if ($etatChoisi === $etatOuverte || $etatChoisi === $etatPassee) {
                // Je prends entre 2 et 5 participants au hasard
                $inscrits = $faker->randomElements($participants, $faker->numberBetween(2, 5));
                foreach ($inscrits as $inscrit) {
                    $sortie->addInscrit($inscrit);
                }
            }

            $manager->persist($sortie);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        // J'indique à Doctrine d'exécuter les fixtures déjà existantes AVANT la mienne pour être sûr que les Lieux et Participants existent déjà.
        return [
            AppFixtures::class,
        ];
    }
}
