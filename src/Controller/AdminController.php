<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Form\SearchParticipantType;
use App\Model\SearchParticipant;
use App\Services\Admin\AdminUserService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function landingAdmin(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/gestion_utilisateurs', name: 'admin_users', methods: ['GET'])]
    public function listUsers(
        Request $request,
        AdminUserService $adminUserService,
        PaginatorInterface $paginator
    ): Response
    {
        //bien un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = new SearchParticipant();
        $form = $this->createForm(SearchParticipantType::class, $search);
        $form->handleRequest($request);

        $qb = $adminUserService->getUserQueryBuilder($search);
        $page = max(1, (int) $request->query->get('page', 1));

        //items par page
        $pagination = $paginator->paginate($qb, $page, 15);

        return $this->render('admin/users/listing.html.twig', [
            'form' => $form->createView(),
            'participants' => $pagination,
        ]);
    }

    #[Route('/admin/gestion_utilisateurs/{id}/edit', name: 'admin_user_edit', methods: ['GET','POST'])]
    public function editUser(
        Participant $participant,
        Request $request,
        AdminUserService $adminUserService,
    ): Response
    {
        //bien un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adminUserService->save($participant);
            $this->addFlash('success', 'Utilisateur modifié.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/modifierParAdmin.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    #[Route('/admin/gestion_utilisateurs/{id}/toggle-actif', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggleActif(
        Participant $participant,
        Request $request,
        AdminUserService $adminUserService,
    ): Response
    {
        //bien un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if(!$this->isCsrfTokenValid('toggle_actif_'.$participant->getId(), $request->request->get('_token')))
        {
            throw $this->createAccessDeniedException();
        }

        $adminUserService->toggleActif($participant);
        $this->addFlash('success', 'Statut utilisateur mis à jour.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/gestion_utilisateurs/{id}/suppression', name:'admin_user_delete', methods:['POST'])]
    public function deleteUser(
        Participant $participant,
        Request $request,
        AdminUserService $adminUserService,
    ): Response
    {
        //bien un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if(!$this->isCsrfTokenValid('delete_user_'.$participant->getId(), $request->request->get('_token')))
        {
            throw $this->createAccessDeniedException();
        }

        $adminUserService->delete($participant);
        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_users');
    }














    //todo: services -etablir la liste des campus -suppression du campus "ciblé" -modifier renvoi un form de modification -ajout de campus si on tape dans la liste
    #[Route('/admin/gestion_campus', name: 'admin_campus')]
    public function listCampus(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    //todo: services -etablir la liste des villes - suppression d'une ville - modification d'une ville -ajout de ville
    #[Route('/admin/gestion_villes', name: 'admin_villes')]
    public function listVilles(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }



}
