<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use App\Entity\Participant;

final class MemberController extends AbstractController
{
    #[Route('/profil/{pseudo}', 
        name: 'app_member_show',
        requirements: ['pseudo' => '^(?!modifier$).+'], 
        methods: ['GET']
    )]
    public function show(
        #[MapEntity(mapping: ['pseudo' => 'pseudo'])]
        Participant $participant): Response
    {
        return $this->render('member/show.html.twig', [
            'participant' => $participant,
        ]);
    }
}
