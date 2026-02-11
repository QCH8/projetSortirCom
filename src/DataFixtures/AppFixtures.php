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

        // création des villes
        $villesArray= [];
        for($i=1; $i<10; $i++ ){
           $ville =  new Ville();
           $ville->setNom($faker->city());
           $ville->setCodePostal($faker->postcode());
           $manager->persist($ville);
           $villesArray[]= $ville;

        }

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


        //création de Campus
        $campusArray =[];
        for($i=1; $i<5; $i++){
            $campus = new Campus();
            $villeRandom = $villesArray[array_rand($villesArray)];
            $campus->setNom($villeRandom->getNom());
            $manager->persist($campus);
            $campusArray[] = $campus;
        }

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
        // création des lieux
        $lieux = [];
        for($i=1; $i<500; $i++){
            $lieu = new Lieu();
            $lieu->setNom($faker->company());
            $lieu->setRue($faker->streetAddress() . $faker->streetName());
            $lieu->setLatitude($faker->latitude());
            $lieu->setLongitude($faker->longitude());
            $lieu->setVille($villesArray[array_rand($villesArray)]);
            $manager->persist($lieu);
            $lieux[] = $lieu;
        }

        $manager->flush();
    }
}
