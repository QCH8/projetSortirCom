<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ){
    }

    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

//         création des villes
//        $villesArray= [];
//        for($i=1; $i<10; $i++ ){
//           $ville =  new Ville();
//           $ville->setNom($faker->city());
//           $ville->setCodePostal($faker->postcode());
//           $manager->persist($ville);
//           $villesArray[]= $ville;
//
//        }

        // --- CRÉATION DES CAMPUS ET DES LIEUX ---
        // 1. Définition des données communes (Campus + Ville)
        $villesEtCampusData = [
            ['nom' => 'Saint-Herblain', 'cp' => '44800'],
            ['nom' => 'Chartres-de-Bretagne', 'cp' => '35131'],
            ['nom' => 'Quimper', 'cp' => '29000'],
            ['nom' => 'Niort', 'cp' => '79000'],
        ];

        $villesArray = [];
        $campusArray = [];

        foreach ($villesEtCampusData as $data) {
            // 2. Création de la Ville
            $ville = new Ville();
            $ville->setNom($data['nom']);
            $ville->setCodePostal($data['cp']);
            $manager->persist($ville);
            $villesArray[] = $ville;

            // 3. Création du Campus correspondant
            $campus = new Campus();
            $campus->setNom($data['nom']);
            $manager->persist($campus);
            $campusArray[] = $campus;
        }

        // création de Campus
//        $campusArray =[];
//        for($i=1; $i<5; $i++){
//            $campus = new Campus();
//            $villeRandom = $villesArray[array_rand($villesArray)];
//            $campus->setNom($villeRandom->getNom());
//            $manager->persist($campus);
//            $campusArray[] = $campus;
//        }

        // Création des états
        $configEtats = [
            "En création" => "ETAT_CREATION",
            "Ouverte" => "ETAT_OUVERTE",
            "Clôturée" => "ETAT_CLOTUREE",
            "En cours" => "ETAT_EN_COURS",
            "Terminée" => "ETAT_TERMINEE",
            "Annulée" => "ETAT_ANNULEE",
            "Historisée" => "ETAT_HISTORISEE"
        ];

        foreach ($configEtats as $libelle => $ref) {
            $etat = new Etat();
            $etat->setLibelle($libelle);
            $manager->persist($etat);
            $this->addReference($ref, $etat);
        }

        // --- CRÉATION DES LIEUX ---
        $lieuxArray = [];
        foreach ($villesArray as $ville) {
            for ($i = 1; $i <= 3; $i++) {
                $lieu = new Lieu();
                $lieu->setNom("Lieu " . $i . " à " . $ville->getNom())
                    ->setRue($faker->streetAddress())
                    ->setLatitude($faker->latitude())
                    ->setLongitude($faker->longitude())
                    ->setVille($ville);
                $manager->persist($lieu);
                $lieuxArray[] = $lieu;
            }
        }

        // --- CREATION DES DES RÔLES ---
        $participants = [];
        // création d'Admin
        $userAdmin = new Participant();
        $userAdmin->setNom($faker->name());
        $userAdmin->setActif(true);
        $userAdmin->setAdministrateur(true);
        $userAdmin->setCampus($campusArray[array_rand(($campusArray))]);
        $userAdmin->setMail("admin@eni.fr");
        $userAdmin->setPrenom($faker->firstName());
        // $userAdmin->setPassword("admin");
        $userAdmin->setPassword($this->hasher->hashPassword($userAdmin, "admin"));
        $userAdmin->setTelephone($faker->phoneNumber());
        $userAdmin->setPseudo("Jean-Eude");
        $manager->persist($userAdmin);
        $participants[] = $userAdmin;

        // création de deux User Non-admin
        for($i=0; $i<2; $i++) {
            $user = new Participant();
            $user->setNom($faker->name());
            $user->setActif(true);
            $user->setAdministrateur(false);
            $user->setCampus($campusArray[array_rand(($campusArray))]);
            $user->setMail("random" . $i . "@eni.fr");
            $user->setPrenom($faker->firstName());
            // $userAdmin->setPassword("admin");
            $user->setPassword($this->hasher->hashPassword($user, "random" . $i));
            $user->setTelephone($faker->phoneNumber());
            $user->setPseudo("random" . $i);
            $manager->persist($user);
            $participants[] = $user;
        }

        // --- CREATION DES PARTICIPANTS ---
        //création de Participants
        for($i=1; $i<100; $i++){
            $user = new Participant();
            $user->setNom($faker->name());
            $user->setActif($faker->boolean(87));
            $user->setAdministrateur(false);
            $user->setCampus($campusArray[array_rand(($campusArray))]);
            $user->setMail($faker->unique()->safeEmail());
            $user->setPrenom($faker->firstName());
            //$user->setPassword("test" . $i);
            $user->setPassword($this->hasher->hashPassword($user, $faker->password() ));
            $user->setTelephone($faker->phoneNumber());
            $user->setPseudo($faker->unique()->userName());
            $manager->persist($user);
            $participants[]=$user;
        }

        // --- CRÉATION DES SORTIES
        $etatsRefs = [
            'ouvert'     => $this->getReference('ETAT_OUVERTE', Etat::class),
            'cloture'    => $this->getReference('ETAT_CLOTUREE', Etat::class),
            'terminee'   => $this->getReference('ETAT_TERMINEE', Etat::class),
            'historisee' => $this->getReference('ETAT_HISTORISEE', Etat::class),
            'creation'   => $this->getReference('ETAT_CREATION', Etat::class),
            'annulee'    => $this->getReference('ETAT_ANNULEE', Etat::class),
        ];

        for ($i = 0; $i <50; $i++) {
            $sortie = new Sortie();

            // On récupère un VRAI participant déjà créé plus haut
            $organisateur = $faker->randomElement($participants);

            // Initialisation des propriétés de base
            $activites = ['Randonnée', 'Soirée', 'Bowling', 'Cinéma', 'Afterwork', 'Pique-nique', 'Concert', 'Visite', 'Laser Game', 'Escape Game'];
            $suffixe = [
                'entre amis',
                'découverte',
                'chez ' . $organisateur->getPrenom(),
                'à ' . $faker->city(),
                'nocturne',
                'spécial étudiant'
            ];
            $sortie->setNom($faker->randomElement($activites) . ' ' . $faker->randomElement($suffixe));
            $sortie->setInfosSortie($faker->text(200));
            $sortie->setDuree($faker->numberBetween(60, 240));
            $sortie->setNbInscriptionsMax($faker->numberBetween(5, 20));

            // Création de dates au hasard pour tester tous les status
            $hasard = $faker->numberBetween(1, 6);
            $dateDebut = new \DateTime();

            switch ($hasard) {
                case 1: // Archivage : sortie terminée depuis plus de 30 jours
                    $dateDebut = $faker->dateTimeBetween('-3 months', '-35 days');
                    $etat = $etatsRefs['historisee'];
                    break;
                case 2: // Sorties finies récemment
                    $dateDebut = $faker->dateTimeBetween('-25 days', '-1 day');
                    $etat = $etatsRefs['terminee'];
                    break;
                case 3: // Sorties futures avec inscriptions déjà fermées
                    $dateDebut = $faker->dateTimeBetween('+1 day', '+5 days');
                    $etat = $etatsRefs['cloture'];
                    break;
                case 4: // Brouillons (en cours de création)
                    $dateDebut = $faker->dateTimeBetween('+10 days', '+20 days');
                    $etat = $etatsRefs['creation'];
                    break;
                case 5: // Sorties annulées
                    $dateDebut = $faker->dateTimeBetween('+5 days', '+15 days');
                    $etat = $etatsRefs['annulee'];
                    break;
                default: // Sorties ouvertes classiques
                    $dateDebut = $faker->dateTimeBetween('+5 days', '+30 days');
                    $etat = $etatsRefs['ouvert'];
                    break;
            }

            $sortie->setDateHeureDebut(\DateTimeImmutable::createFromMutable($dateDebut));

            // La date limite d'inscription est toujours fixée quelques jours avant le début
            $dateLimite = (clone $dateDebut)->modify('-' . $faker->numberBetween(2, 5) . ' days');
            $sortie->setDateLimiteInscription(\DateTimeImmutable::createFromMutable($dateLimite));

            $sortie->setEtat($etat);

            // Attribution d'un organisateur et du campus correspondant
            $sortie->setOrganisateur($organisateur);
            $sortie->setCampus($organisateur->getCampus());
            $sortie->setLieu($faker->randomElement($lieuxArray));

            // Ajout de participants si la sortie est déjà publiée
            if ($etat->getLibelle() !== 'En création') {
                // Pour tester le statut "Clôturée", on remplit toutes les places
                $nbInscrits = ($etat->getLibelle() === 'Clôturée')
                    ? $sortie->getNbInscriptionsMax()
                    : $faker->numberBetween(1, $sortie->getNbInscriptionsMax() - 1);

                $inscritsPossibles = $faker->randomElements($participants, $nbInscrits);
                foreach ($inscritsPossibles as $inscrit) {
                    $sortie->addInscrit($inscrit);
                }
            }

            $manager->persist($sortie);
        }

        $manager->flush();
    }
}
