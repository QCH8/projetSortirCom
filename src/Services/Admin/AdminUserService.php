<?php

namespace App\Services\Admin;

use App\Entity\Participant;
use App\Model\SearchParticipant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AdminUserService
{
    public function __construct(
      private ParticipantRepository $participants,
      private EntityManagerInterface $manager,
    ){}

    /** @return Participant[]*/
    public function getUserQueryBuilder(SearchParticipant $search): QueryBuilder
    {
        return $this->participants->queryBuilderForAdminList($search);
    }

    public function toggleActif(Participant $participant): void
    {
        $participant->setActif(!$participant->isActif());
        $this->manager->flush();
    }

    public function delete(Participant $participant): void
    {
        $this->manager->remove($participant);
        $this->manager->flush();
    }

    public function save(Participant $participant): void
    {
        $this->manager->flush();
    }



}
