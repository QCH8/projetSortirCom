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
        // On crée le QueryBuilder de base sur l'entité Sortie (alias 's')
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'e') // Jointure pour filtrer sur le libellé de l'état
            ->addSelect('e')
            ->leftJoin('s.campus', 'c') // Jointure pour le filtre par campus
            ->addSelect('c');

        // --- RÈGLE DE VISIBILITÉ ---
        // On ne veut voir que les sorties publiées OU celles dont on est l'organisateur
        $qb->andWhere(
            $qb->expr()->orX(
                'e.libelle != :etatCreer',
                's.organisateur = :user'
            )
        )
            ->setParameter('etatCreer', 'En création')
            ->setParameter('user', $utilisateur);

        // --- FILTRES DYNAMIQUES (FORMULAIRE) ---

        // 1. Filtre par Campus
        if (!empty($donnees['campus'])) {
            $qb->andWhere('s.campus = :campus')
                ->setParameter('campus', $donnees['campus']);
        }

        // 2. Filtre par Nom (recherche partielle)
        if (!empty($donnees['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $donnees['nom'] . '%');
        }

        // 3. Filtre par Dates (Intervalle)
        if (!empty($donnees['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $donnees['dateDebut']);
        }
        if (!empty($donnees['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $donnees['dateFin']);
        }

        // 4. Filtre "Sorties dont je suis l'organisateur"
        if (!empty($donnees['isOrganisateur'])) {
            $qb->andWhere('s.organisateur = :user');
            // Le paramètre :user est déjà défini plus haut
        }

        // 5. Filtre "Sorties auxquelles je suis inscrit"
        if (!empty($donnees['isInscrit'])) {
            $qb->andWhere(':user MEMBER OF s.inscrits');
        }

        // 6. Filtre "Sorties auxquelles je ne suis PAS inscrit"
        if (!empty($donnees['isNotInscrit'])) {
            $qb->andWhere(':user NOT MEMBER OF s.inscrits');
        }

        // 7. Filtre "Sorties passées"
        if (!empty($donnees['isTerminee'])) {
            $qb->andWhere('e.libelle = :etatPasser')
                ->setParameter('etatPasser', 'Passée');
        }

        // On ordonne par date de début (la plus proche en premier)
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
