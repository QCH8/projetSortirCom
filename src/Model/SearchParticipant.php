<?php

namespace App\Model;

use App\Entity\Campus;

class SearchParticipant
{

    public ?Campus $campus = null;
    public ?string $nom = null;
    public ?bool $actifsSeulement = true;
}
