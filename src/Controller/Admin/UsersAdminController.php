<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users', name: 'admin_users_')]
final class UsersAdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $req): Response
    {
        $search = trim((string) $req->query->get('q', ''));
        $page = max(1, $req->query->getInt('page', 1));
        $perPage = 20;
        $paginator = $this->repo->paginatedSearch($search, $page, $perPage);

        return $this->render('admin/users/index.html.twig', [
            'paginator' => $paginator,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'total' => \count($paginator),
        ]);
    }

    #[Route('/{uid}', name: 'show', methods: ['GET'], requirements: ['uid' => '[A-Z0-9]{26}'])]
    public function show(string $uid): Response
    {
        $user = $this->findOrFail($uid);

        return $this->render('admin/users/show.html.twig', ['user' => $user]);
    }

    #[Route('/{uid}/promote', name: 'promote', methods: ['POST'], requirements: ['uid' => '[A-Z0-9]{26}'])]
    public function promote(string $uid, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('promote'.$uid, (string) $req->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $user = $this->findOrFail($uid);
        $roles = $user->getRoles();
        $hasAdmin = \in_array('ROLE_ADMIN', $roles, true);
        $newRoles = array_values(array_diff($roles, ['ROLE_ADMIN']));
        if (!$hasAdmin) {
            $newRoles[] = 'ROLE_ADMIN';
        }
        $user->setRoles(array_values(array_unique($newRoles)));
        $this->em->flush();
        $this->addFlash('success', 'Rôles mis à jour.');

        return $this->redirectToRoute('admin_users_show', ['uid' => $uid]);
    }

    #[Route('/{uid}/delete', name: 'delete', methods: ['POST'], requirements: ['uid' => '[A-Z0-9]{26}'])]
    public function delete(string $uid, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$uid, (string) $req->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $user = $this->findOrFail($uid);
        $this->em->remove($user);
        $this->em->flush();
        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirectToRoute('admin_users_index');
    }

    private function findOrFail(string $uid): User
    {
        $user = $this->repo->findOneBy(['uid' => $uid]);
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        return $user;
    }
}
