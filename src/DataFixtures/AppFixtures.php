<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Participant;
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

        // Fixture de l'états
        $etatArray = ["En création", "Ouverte", "Clôturée", "En cours", "Terminée", "Annulée", "Historisée"];
        for($i=0; $i<count($etatArray); $i++){
            $etat = new Etat();
            $etat->setLibelle($etatArray[$i]);
            $manager->persist($etat);
        }


        //création Campus
        $campusArray =[];
        for($i=1; $i<5; $i++){
            $campus = new Campus();
            $villeRandom = $villesArray[array_rand($villesArray)];
            $campus->setNom($villeRandom->getNom());
            $manager->persist($campus);
            $campusArray[] = $campus;
        }

        // création d'Admin et Participants
        $userAdmin = new Participant();
        $userAdmin->setNom($faker->name());
        $userAdmin->setActif(true);
        $userAdmin->setAdministrateur(false);
        $userAdmin->setCampus($campusArray[array_rand(($campusArray))]);
        $userAdmin->setMail("admin@eni.fr");
        $userAdmin->setPrenom($faker->firstName());
        // $userAdmin->setPassword("admin");
        $userAdmin->setPassword($this->hasher->hashPassword($userAdmin, "admin"));
        $userAdmin->setTelephone($faker->phoneNumber());
        $userAdmin->setPseudo("Jean-Eude");
        $manager->persist($userAdmin);

        for($i=1; $i<100; $i++){
            $user = new Participant();
            $user->setNom($faker->name());
            $user->setActif($faker->boolean(87));
            $user->setAdministrateur(false);
            $user->setCampus($campusArray[array_rand(($campusArray))]);
            $user->setMail($faker->email());
            $user->setPrenom($faker->firstName());
            //$user->setPassword("test" . $i);
            $user->setPassword($this->hasher->hashPassword($user, $faker->password() ));
            $user->setTelephone($faker->phoneNumber());
            $user->setPseudo($faker->name());
            $manager->persist($user);
        }

        $manager->flush();
    }
}
