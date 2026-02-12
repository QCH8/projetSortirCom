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
            ->join('s.etat', 'e')
            ->addSelect('e')
            ->join('s.campus', 'c')
            ->addSelect('c')
            ->join('s.organisateur', 'o')
            ->addSelect('o')
            ->leftJoin('s.inscrits', 'i')
            ->addSelect('i');

        // --- 1. RÈGLE DE VISIBILITÉ ---
        // On ne voit les "En création" que si on en est l'organisateur
        $qb->andWhere(
            $qb->expr()->orX(
                'e.libelle != :etatCreer',
                's.organisateur = :user'
            )
        )
            ->setParameter('etatCreer', 'En création')
            ->setParameter('user', $utilisateur);

        // --- 2. FILTRES DU FORMULAIRE D'ACCUEIL ---

        if (!empty($donnees['campus'])) {
            $qb->andWhere('s.campus = :campus')
                ->setParameter('campus', $donnees['campus']);
        }

        if (!empty($donnees['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $donnees['nom'] . '%');
        }

        // Filtres de dates "Entre le... et le..."
        if (!empty($donnees['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $donnees['dateDebut']);
        }

        if (!empty($donnees['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $donnees['dateFin']);
        }

        // Filtres à cocher (Checkbox)
        if (!empty($donnees['isOrganisateur'])) {
            $qb->andWhere('s.organisateur = :user');
        }

        if (!empty($donnees['isInscrit'])) {
            $qb->andWhere(':user MEMBER OF s.inscrits');
        }

        if (!empty($donnees['isNotInscrit'])) {
            $qb->andWhere(':user NOT MEMBER OF s.inscrits');
        }

        if (!empty($donnees['isTerminee'])) {
            // Correspond à l'état "Terminée"
            $qb->andWhere('e.libelle = :etatTerminer')
                ->setParameter('etatTerminer', 'Terminée');
        }

        // --- 3. RÈGLE D'ARCHIVAGE AUTOMATIQUE ---
        // On exclut les sorties "Historisées" (plus d'un mois) de la liste principale
        $qb->andWhere('e.libelle != :historisee')
            ->setParameter('historisee', 'Historisée');

        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }
    }
