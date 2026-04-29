<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\CityRepository;
use App\Repository\GameScoreRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly GameScoreRepository $scores,
        private readonly CityRepository $cities,
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            'usersCount' => $this->users->count([]),
            'scoresCount' => $this->scores->count([]),
            'citiesCount' => $this->cities->count([]),
            'topScores' => $this->scores->findTopN(10),
            'last7days' => $this->scores->countByDayLast7(),
            'avgScore' => $this->scores->getAverageScore(),
            'recentUsers' => $this->users->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }
}
