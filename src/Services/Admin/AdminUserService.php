<?php

namespace App\Services\Admin;

use App\Entity\Participant;
use App\Model\SearchParticipant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use http\Exception\InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserService
{
    public function __construct(
        private ParticipantRepository $participants,
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
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

    public function create(Participant $participant, string $plainPassword):void
    {
        $plainPassword = trim($plainPassword);
        if($plainPassword === ''){
            throw new InvalidArgumentException('Le mot de passe est obligatoire');
        }

        $hashed = $this->passwordHasher->hashPassword($participant, $plainPassword);
        $participant->setPassword($hashed);

        $this->manager->persist($participant);
        $this->manager->flush();

    }

}
