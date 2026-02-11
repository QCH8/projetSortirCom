<?php

namespace App\Twig\Components;

use App\Repository\ParticipantRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class UniqueFieldChecker
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $value = ''; // La valeur saisie (anciennement $pseudo)

    #[LiveProp]
    public string $fieldName = ''; // Le nom de la colonne en base de données (ex: 'pseudo', 'mail')

    #[LiveProp]
    public string $initialValue = ''; // La valeur actuelle de l'utilisateur (pour l'ignorer)

    #[LiveProp]
    public string $inputName = ''; // Le nom complet du champ formulaire (ex: 'participant[pseudo]')

    #[LiveProp]
    public string $label = ''; // Le label à afficher (ex: 'Pseudo', 'Email')
    
    #[LiveProp]
    public string $errorMessage = ''; // Message d'erreur personnalisé

    public function __construct(private ParticipantRepository $repository) {}

    public function getValidationError(): ?string
    {
        $valueToCheck = trim($this->value);
        
        // Pas de check si vide ou si c'est la valeur initiale
        if (strlen($valueToCheck) < 3 || $valueToCheck === $this->initialValue) {
            return null;
        }

        // Vérification dynamique selon le champ
        $exists = $this->repository->findOneBy([$this->fieldName => $valueToCheck]);
        
        if ($exists) {
            return $this->errorMessage ?: "Ce " . strtolower($this->label) . " est déjà utilisé.";
        }

        return null;
    }
}
