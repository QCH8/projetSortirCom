<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Participant;
use App\Form\AdminCreateParticipantType;
use App\Form\AdminModifyParticipantType;
use App\Form\SearchParticipantType;
use App\Model\SearchParticipant;
use App\Services\Admin\AdminUserService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class AdminController extends AbstractController
{

    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
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

        $form = $this->createForm(AdminModifyParticipantType::class, $participant);
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

    #[Route('/admin/gestion_utilisateurs/creation', name:'admin_user_create', methods:['GET', 'POST'])]
    public function createUser(
        Request $request,
        AdminUserService $adminUserService,
    ): Response
    {
        //bien un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $participant = new Participant();

        $participant->setActif(true);
        $participant->setAdministrateur(false);

        $form = $this->createForm(AdminCreateParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $adminUserService->create($participant, $plainPassword);
            $this->addFlash('success', 'Utilisateur créé.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/creerParAdmin.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }
}