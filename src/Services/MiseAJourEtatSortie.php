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

    public function synchroniserSiBesoin(
        Sortie $sortie
    ): bool
    {
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        $etatOuverte = $this->etatRepo->findOneBy(['libelle'=>'Ouverte']);
        $etatCloturee = $this->etatRepo->findOneBy(['libelle'=>'Clôturée']);

        //Si pas ces états en BDD → on ne fait rien
        if(!$etatOuverte || !$etatCloturee){ return false;}

        //Verification de l'état de la Sortie (nbInscrit, dateLimite, etat)
        $maxAtteint = count($sortie->getInscrits()) >= $sortie->getNbInscriptionsMax();
        $dateLimiteDepassee = $sortie->getDateLimiteInscription() <= $now;
        $etatActuel = $sortie->getEtat()?->getId();
        //Si la date limite est dépassée ou le nombre d'inscritMax est atteint → Cloture de l'event
        if (($maxAtteint || $dateLimiteDepassee) && $etatActuel !== $etatCloturee->getId()){
            $sortie->setEtat($etatCloturee);
            $this->manager->persist($sortie);
            $this->manager->flush();
            return true;
        }
        //Si Sortie cloturée mais qu'il reste des places et date limite non dépassée → Ouverture de l'event
        if (!($dateLimiteDepassee || $maxAtteint) && $etatActuel === $etatCloturee->getId()) {
            $sortie->setEtat($etatOuverte);
            $this->manager->persist($sortie);
            $this->manager->flush();
            return true;
        }
        return false;
    }

    /*version LISTE
    * @param iterable<Sortie> $sorties
    */
    public function synchroniserSiBesoinListe(
        Sortie|iterable $cible
    ): int
    {
        $now = new \DateTimeImmutable("now", new \DateTimeZone("Europe/Paris"));

        $etatOuverte = $this->etatRepo->findOneBy(['libelle'=>'Ouverte']);
        $etatCloturee = $this->etatRepo->findOneBy(['libelle'=>'Clôturée']);
        //Si pas ces états en BDD → on ne fait rien
        if(!$etatOuverte || !$etatCloturee){ return 0;}

        $sorties = $cible instanceof Sortie ? [$cible]: $cible;

        $changed = 0;
        //Verif bien que l'on est bien sur des instances de Sortie
        foreach($sorties as $sortie){
            if (!$sortie instanceof Sortie){
                continue;
            }

            $maxAtteint = count($sortie->getInscrits()) >= $sortie->getNbInscriptionsMax();
            $dateLimiteDepassee = $sortie->getDateLimiteInscription() <= $now;
            $etatActuelId = $sortie->getEtat()->getId();
            $nouvelEtat = null;

            //Doit être clôturée ?
            if ($maxAtteint || $dateLimiteDepassee){
                if($etatActuelId !== $etatCloturee->getId()){
                    $nouvelEtat = $etatCloturee;
                }
            } else { //peut être réouvert?
                if($etatActuelId === $etatCloturee->getId()){
                    $nouvelEtat = $etatOuverte;
                }
            }
            if ($nouvelEtat !== null){
                $sortie->setEtat($nouvelEtat);
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
