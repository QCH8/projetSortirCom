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
        $faker = Factory::create('fr_FR');

        // 1. RÉCUPÉRATION DES DÉPENDANCES
        $campuses = $manager->getRepository(Campus::class)->findAll();
        $lieux = $manager->getRepository(Lieu::class)->findAll();
        $participants = $manager->getRepository(Participant::class)->findAll();


        if (empty($campuses) || empty($lieux) || empty($participants)) {
            echo "ATTENTION : Données manquantes.\n";
            return;
        }

        // 2. PRÉPARATION DES ÉTATS (Via références partagées)
        $etatOuverte = $this->getReference('ETAT_OUVERTE', Etat::class);
        $etatTerminee = $this->getReference('ETAT_TERMINEE', Etat::class);
        $etatCloturee = $this->getReference('ETAT_CLOTUREE', Etat::class);

        // 3. GÉNÉRATION DES 50 SORTIES
        for ($i = 0; $i < 50; $i++) {
            $sortie = new Sortie();

            // --- NOM ET INFOS ---
            $activites = ['Randonnée', 'Soirée', 'Bowling', 'Cinéma', 'Afterwork', 'Pique-nique', 'Concert', 'Visite', 'Laser Game', 'Escape Game'];
            $suffixe = ['entre amis', 'découverte', 'chez ' . $faker->firstName(), 'à ' . $faker->city(), 'nocturne', 'spécial étudiant'];
            $sortie->setNom($faker->randomElement($activites) . ' ' . $faker->randomElement($suffixe));
            $sortie->setInfosSortie($faker->text(200));
            $sortie->setDuree($faker->numberBetween(60, 240)); 
            $sortie->setNbInscriptionsMax($faker->numberBetween(5, 20));

            // --- LOGIQUE TEMPORELLE ET ÉTAT (4 CAS POUR TESTER L'HISTORISATION) ---
            $hasard = $faker->numberBetween(1, 4);

            if ($hasard === 1) {
                // CAS 1 : Sortie TRÈS VIEILLE (> 1 mois) -> Devrait apparaître "Historisée" dans ton tableau
                $dateDebut = $faker->dateTimeBetween('-5 months', '-35 days');
                $etatChoisi = $etatTerminee;
            } elseif ($hasard === 2) {
                // CAS 2 : Sortie RÉCENTE PASSÉE (< 1 mois) -> Doit rester "Terminée"
                $dateDebut = $faker->dateTimeBetween('-20 days', '-1 day');
                $etatChoisi = $etatTerminee;
            } elseif ($hasard === 3) {
                // CAS 3 : Sortie Future
                $dateDebut = $faker->dateTimeBetween('+5 days', '+2 months');
                $etatChoisi = $etatOuverte;
            } else {
                // CAS 4 : Sortie Future dont les inscriptions sont finies
                $dateDebut = $faker->dateTimeBetween('+1 day', '+4 days');
                $etatChoisi = $etatCloturee;
            }

            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));
            $dateLimite = (clone $dateDebut)->modify('-2 days');
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));
            $sortie->setEtat($etatChoisi);

            // --- RELATIONS ET PARTICIPANTS ---
            $sortie->setCampus($faker->randomElement($campuses));
            $sortie->setLieu($faker->randomElement($lieux));
            $sortie->setOrganisateur($faker->randomElement($participants));

            if ($etatChoisi->getLibelle() !== 'En création') {
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
        return [AppFixtures::class];
    }
}