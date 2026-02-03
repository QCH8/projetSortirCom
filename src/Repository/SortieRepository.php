<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Entity\Campus;
use App\Entity\Participant;
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

    // Récupère les sorties en fonction des critères de recherche
    public function trouverParCriteres(
        Campus $campusChoisi,
        Participant $utilisateurConnecte,
        array $criteres
    ): array {
        // Initialisation avec jointures pour l'état (e) et l'organisateur (o)
        $gestionnaireRequete = $this->createQueryBuilder('s')
            ->join('s.etat', 'e')
            ->join('s.organisateur', 'o')
            ->addSelect('e', 'o');

// 1. FILTRE OBLIGATOIRE : Campus
        $gestionnaireRequete->andWhere('s.campus = :campus')
        ->setParameter('campus', $campusChoisi);

        // 2. RECHERCHE TEXTUELLE : Le nom contient...
        if (!empty($criteres['rechercheNom'])) {
            $gestionnaireRequete->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $criteres['rechercheNom'] . '%');
        }

        // 3. INTERVALLE DE DATES : Entre... et...
        if (!empty($criteres['dateDebut'])) {
            $gestionnaireRequete->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $criteres['dateDebut']);
        }
        if (!empty($criteres['dateFin'])) {
            $gestionnaireRequete->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $criteres['dateFin']);
        }

        // 4. VISIBILITÉ GÉNÉRALE (Règle métier : qui a le droit de voir quoi)
        // On ne voit pas les brouillons ("En création") des autres organisateurs
        $gestionnaireRequete->andWhere(
            $gestionnaireRequete->expr()->orX(
                'e.libelle != :etatEnCreation',
                'o = :utilisateur'
            )
        )
            ->setParameter('etatEnCreation', 'En création');

        // 5. CASES À COCHER (Logique d'inclusion "OU")
        $groupeOu = $gestionnaireRequete->expr()->orX();

        if (!empty($criteres['estOrganisateur'])) {
            $groupeOu->add('o = :utilisateur');
        }
        if (!empty($criteres['estInscrit'])) {
            $groupeOu->add(':utilisateur MEMBER OF s.participants');
        }
        if (!empty($criteres['sortiesTerminees'])) {
            $groupeOu->add('e.libelle = :etatPassee');
            $gestionnaireRequete->setParameter('etatPassee', 'Passée');
        }

        if ($groupeOu->count() > 0) {
            $gestionnaireRequete->andWhere($groupeOu);
        }

        // 6. FILTRE D'EXCLUSION : Non inscrit (Logique "ET")
        if (!empty($criteres['nEstPasInscrit'])) {
            $gestionnaireRequete->andWhere(':utilisateur NOT MEMBER OF s.participants');
        }

        // Paramètre commun pour l'utilisateur connecté
        $gestionnaireRequete->setParameter('utilisateur', $utilisateurConnecte);

        // Tri chronologique
        $gestionnaireRequete->orderBy('s.dateHeureDebut', 'ASC');

        return $gestionnaireRequete->getQuery()->getResult();
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

