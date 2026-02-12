<?php

namespace App\Model;

use App\Entity\Campus;

class SearchParticipant
{

    private ?Campus $campus = null;
    private ?string $nom = null;
    private ?bool $actifSeulement = true;

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): void
    {
        $this->campus = $campus;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): void
    {
        $this->nom = $nom;
    }

    public function getActifSeulement(): ?bool
    {
        return $this->actifSeulement;
    }

    public function setActifSeulement(?bool $actifsSeulement): void
    {
        $this->actifSeulement = $actifsSeulement;
    }



}
