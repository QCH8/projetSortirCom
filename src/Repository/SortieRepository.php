<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }
    /**
     * Récupère les sorties en fonction des filtres de recherche et des règles de visibilité
     */
    public function findSearch(Participant $utilisateur, array $donnees): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.etat', 'e')   // J'ai mis join au lieu de leftJoin pour forcer l'existence d'un état
            ->addSelect('e')
            ->join('s.campus', 'c')
            ->addSelect('c')
            ->join('s.organisateur', 'o') // Utile pour afficher le nom sans refaire de requête
            ->addSelect('o');

        // --- 1. RÈGLE DE VISIBILITÉ (Optimisée) ---
        // Soit l'état n'est PAS 'En création' (donc Ouverte, Clôturée, Passée...)
        // Soit JE SUIS l'organisateur (peu importe l'état)
        $qb->andWhere(
            $qb->expr()->orX(
                'e.libelle != :etatCreer',
                's.organisateur = :user'
            )
        )
            ->setParameter('etatCreer', 'En création') // VÉRIFIEZ CE TEXTE AVEC VOTRE BDD !
            ->setParameter('user', $utilisateur);

        // --- 2. FILTRES FORMULAIRE ---

        if (!empty($donnees['campus'])) {
            $qb->andWhere('s.campus = :campus')
                ->setParameter('campus', $donnees['campus']);
        }

        if (!empty($donnees['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $donnees['nom'] . '%');
        }

        if (!empty($donnees['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $donnees['dateDebut']);
        }

        if (!empty($donnees['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $donnees['dateFin']);
        }

        if (!empty($donnees['isOrganisateur'])) {
            $qb->andWhere('s.organisateur = :user');
        }

        // "MEMBER OF" est la façon correcte de vérifier une collection ManyToMany en DQL
        if (!empty($donnees['isInscrit'])) {
            $qb->andWhere(':user MEMBER OF s.inscrits');
        }

        if (!empty($donnees['isNotInscrit'])) {
            $qb->andWhere(':user NOT MEMBER OF s.inscrits');
        }

        if (!empty($donnees['isTerminee'])) {
            $qb->andWhere('e.libelle = :etatPasser')
                ->setParameter('etatPasser', 'Passée'); // VÉRIFIEZ CE TEXTE AVEC VOTRE BDD !
        }

        // --- 3. RÈGLE D'ARCHIVAGE (Bonus) ---
        // Ne pas afficher les sorties "historisées" (vieilles de plus d'un mois)
        // Sauf si c'est pour l'historique ou si on veut explicitement les voir.
        // Vérifiez si vous avez un état "Historisée" ou si c'est calculé par date.
        // Exemple :
        // $qb->andWhere('e.libelle != :etatArchive')
        //    ->setParameter('etatArchive', 'Historisée');

        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }

}
    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
