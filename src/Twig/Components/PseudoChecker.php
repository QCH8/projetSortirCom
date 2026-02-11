<?php

namespace App\Twig\Components;

use App\Repository\ParticipantRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class PseudoChecker
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $pseudo = '';

    #[LiveProp]
    public string $inputName = 'pseudo';

    #[LiveProp]
    public string $initialPseudo = '';

    public function __construct(private ParticipantRepository $repository) {}

    // Cette fonction vérifie en temps réel si le pseudo existe
    public function getErrorMessage(): ?string
    {
        $pseudoToCheck = trim($this->pseudo);
        // On ne check pas si trop court ou si c'est le pseudo actuel de l'utilisateur
        if (strlen($pseudoToCheck) < 3 || $pseudoToCheck === $this->initialPseudo) {
            return null;
        }

        $exists = $this->repository->findOneBy(['pseudo' => $pseudoToCheck]);
        return $exists ? 'Ce pseudo est déjà pris.' : null;
    }
}