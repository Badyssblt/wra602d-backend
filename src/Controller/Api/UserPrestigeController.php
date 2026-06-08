<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ProgressionPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Prestige flow: once the player reaches MAX_LEVEL, they may "prestige" to
 * reset their level back to 0 in exchange for a permanent +10 % bonus on every
 * future leaderboard score and every quest XP reward.
 */
#[Route(path: '/api/users/me/prestige', name: 'api_users_me_prestige', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class UserPrestigeController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        if ($user->getLevel() < ProgressionPolicy::MAX_LEVEL) {
            return new JsonResponse([
                'message' => sprintf(
                    'Niveau %d requis pour prestiger (vous êtes niveau %d).',
                    ProgressionPolicy::MAX_LEVEL,
                    $user->getLevel(),
                ),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user
            ->setPrestigeLevel($user->getPrestigeLevel() + 1)
            ->setXp(0);
        $this->em->flush();

        return new JsonResponse([
            'prestigeLevel' => $user->getPrestigeLevel(),
            'xp' => $user->getXp(),
            'level' => $user->getLevel(),
            'multiplier' => ProgressionPolicy::scoreMultiplier($user->getPrestigeLevel()),
        ]);
    }
}
