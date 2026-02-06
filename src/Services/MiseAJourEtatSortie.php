<?php

namespace App\Services;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class MiseAJourEtatSortie
{
    public function __construct(
      private EtatRepository $etatRepo,
      private EntityManagerInterface $manager,
    ){}

    public function cloturerSiBesoin(
        Sortie $sortie
    ): bool
    {
        $now = new \DateTimeImmutable();

        $etatOuverte = $this->etatRepo->findOneBy(['libelle'=>'Ouverte']);
        $etatCloturee = $this->etatRepo->findOneBy(['libelle'=>'Clôturée']);

        //Si pas ces états en BDD → on ne fait rien
        if(!$etatOuverte || !$etatCloturee){ return false;}

        //Verif la Sortie est bien ouverte
        if($sortie->getEtat()->getId() !== $etatOuverte->getId()){ return false;}

        $maxAtteint = count($sortie->getInscrits()) >= $sortie->getNbInscriptionsMax();
        $dateLimiteDepassee = $sortie->getDateLimiteInscription() <= $now;

        if ($maxAtteint || $dateLimiteDepassee){
            $sortie->setEtat($etatCloturee);
            $this->manager->persist($sortie);
            $this->manager->flush();
            return true;
        }
        return false;
    }

    /*version LISTE
    * @param iterable<Sortie> $sorties
    */
    public function cloturerSiBesoinListe(
        iterable $sorties
    ): int
    {
        $now = new \DateTimeImmutable();

        $etatOuverte = $this->etatRepo->findOneBy(['libelle'=>'Ouverte']);
        $etatCloturee = $this->etatRepo->findOneBy(['libelle'=>'Clôturée']);

        //Si pas ces états en BDD → on ne fait rien
        if(!$etatOuverte || !$etatCloturee){ return 0;}

        $changed = 0;

        foreach($sorties as $sortie){
            if ($sortie->getEtat()->getId() !== $etatOuverte->getId()){
                continue;
            }

            $maxAtteint = count($sortie->getInscrits()) >= $sortie->getNbInscriptionsMax();
            $dateLimiteDepassee = $sortie->getDateLimiteInscription() <= $now;

            if ($maxAtteint || $dateLimiteDepassee){
                $sortie->setEtat($etatCloturee);
                $this->manager->persist($sortie);
                $changed++;
            }
        }
        if ($changed > 0){
            $this->manager->flush();
        }
        return $changed;
    }
}
