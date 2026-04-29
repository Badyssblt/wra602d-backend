<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\GameScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/scores', name: 'admin_scores_')]
final class ScoresAdminController extends AbstractController
{
    public function __construct(
        private readonly GameScoreRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $req): Response
    {
        $q = trim((string) $req->query->get('q', ''));
        $min = $req->query->get('min');
        $max = $req->query->get('max');

        $qb = $this->repo->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->orderBy('s.score', 'DESC')
            ->setMaxResults(100);

        if ('' !== $q) {
            $qb->andWhere('LOWER(u.pseudonym) LIKE :q OR LOWER(u.email) LIKE :q')
                ->setParameter('q', '%'.strtolower($q).'%');
        }
        if (null !== $min && '' !== $min) {
            $qb->andWhere('s.score >= :min')->setParameter('min', (int) $min);
        }
        if (null !== $max && '' !== $max) {
            $qb->andWhere('s.score <= :max')->setParameter('max', (int) $max);
        }

        return $this->render('admin/scores/index.html.twig', [
            'scores' => $qb->getQuery()->getResult(),
            'q' => $q,
            'min' => $min,
            'max' => $max,
        ]);
    }

    #[Route('/{uid}/delete', name: 'delete', methods: ['POST'], requirements: ['uid' => '[A-Z0-9]{26}'])]
    public function delete(string $uid, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$uid, (string) $req->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $score = $this->repo->findOneBy(['uid' => $uid]);
        if (null === $score) {
            throw $this->createNotFoundException();
        }
        $this->em->remove($score);
        $this->em->flush();
        $this->addFlash('success', 'Score supprimé.');

        return $this->redirectToRoute('admin_scores_index');
    }
}
