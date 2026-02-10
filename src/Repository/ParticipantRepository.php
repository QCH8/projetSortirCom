<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Model\SearchParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }


    public function loadUserByIdentifier(string $identifier): ?Participant
    {
        //Traitement des chaines de caractères
        $identifier = trim($identifier);
        $identifierLower = mb_strtolower($identifier);

        //Connexion avec pseudo ou mail, si actif
        return $this->createQueryBuilder("participant")
            ->andWhere("(LOWER(participant.mail) = :mailLower OR participant.pseudo = :identifier)")
            ->andWhere("participant.actif = true")
            ->setParameter('identifier', $identifier)
            ->setParameter('mailLower', $identifierLower)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForAdminList(SearchParticipant $search): array
    {
        $baseRequete = $this->createQueryBuilder('p')
            ->leftJoin('p.campus', 'c')->addSelect('c')
            ->orderBy('p.nom', 'ASC');

        if($search->getCampus()){
            $baseRequete->andWhere('p.campus = :campus')->setParameter('campus', $search->getCampus());
        }

        if($search->getNom()){
            $baseRequete->andWhere('LOWER(p.nom) LIKE :q OR LOWER(p.prenom) LIKE :q OR LOWER(p.email) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search->getNom())).'%');
        }

        if($search->getActifSeulement() === true){
            $baseRequete->andWhere('p.actif = true');
        }
        return $baseRequete->getQuery()->getResult();
    }


    //    /**
    //     * @return Participant[] Returns an array of Participant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
