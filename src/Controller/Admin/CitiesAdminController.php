<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\CityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/cities', name: 'admin_cities_')]
final class CitiesAdminController extends AbstractController
{
    public function __construct(private readonly CityRepository $repo)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/cities/index.html.twig', [
            'cities' => $this->repo->findBy([], ['updatedAt' => 'DESC'], 100),
        ]);
    }

    #[Route('/{uid}', name: 'show', methods: ['GET'], requirements: ['uid' => '[A-Z0-9]{26}'])]
    public function show(string $uid): Response
    {
        $city = $this->repo->findOneBy(['uid' => $uid]);
        if (null === $city) {
            throw $this->createNotFoundException();
        }

        $grid = [];
        foreach ($city->getBuildings() as $b) {
            $grid[$b->getPosX().'-'.$b->getPosZ()] = $b->getType();
        }

        return $this->render('admin/cities/show.html.twig', [
            'city' => $city,
            'grid' => $grid,
        ]);
    }
}
