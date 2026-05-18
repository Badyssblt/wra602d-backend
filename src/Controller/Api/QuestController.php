<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserQuestCompletion;
use App\Repository\UserQuestCompletionRepository;
use App\Service\ProgressionPolicy;
use App\Service\QuestCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoints for the daily/weekly quest system.
 *
 *   GET  /api/quests           → catalogue + progression for the current user
 *   POST /api/quests/{code}/claim → claim a completed quest (server validates)
 */
#[Route(path: '/api/quests', name: 'api_quests_')]
#[IsGranted('ROLE_USER')]
final class QuestController extends AbstractController
{
    public function __construct(
        private readonly QuestCatalog $catalog,
        private readonly UserQuestCompletionRepository $completions,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '', methods: ['GET'], name: 'list')]
    public function list(): JsonResponse
    {
        $user = $this->getUserOrFail();
        $now = new \DateTimeImmutable();

        $quests = $this->catalog->all();
        // Map of "code|periodKey" → claimed, so a quest claimed in a previous
        // period doesn't leak into today's list.
        $claimedToday = $this->completions->findClaimedCodes(
            $user,
            array_column(array_filter($quests, fn(array $q) => $q['kind'] === QuestCatalog::KIND_DAILY), 'code'),
            QuestCatalog::periodKey(QuestCatalog::KIND_DAILY, $now),
        );
        $claimedWeek = $this->completions->findClaimedCodes(
            $user,
            array_column(array_filter($quests, fn(array $q) => $q['kind'] === QuestCatalog::KIND_WEEKLY), 'code'),
            QuestCatalog::periodKey(QuestCatalog::KIND_WEEKLY, $now),
        );

        $payload = array_map(
            function (array $quest) use ($user, $now, $claimedToday, $claimedWeek): array {
                $claimedList = $quest['kind'] === QuestCatalog::KIND_WEEKLY ? $claimedWeek : $claimedToday;
                $progress = $this->catalog->bestProgressFor($user, $quest, $now);
                return [
                    'code'      => $quest['code'],
                    'label'     => $quest['label'],
                    'kind'      => $quest['kind'],
                    'metric'    => $quest['metric'],
                    'target'    => $quest['target'],
                    'xpReward'  => $quest['xpReward'],
                    'progress'  => $progress,
                    'completed' => $progress >= $quest['target'],
                    'claimed'   => in_array($quest['code'], $claimedList, true),
                ];
            },
            $quests,
        );

        return new JsonResponse(['quests' => $payload]);
    }

    #[Route(path: '/{code}/claim', methods: ['POST'], name: 'claim', requirements: ['code' => '[a-z0-9_]+'])]
    public function claim(string $code): JsonResponse
    {
        $user = $this->getUserOrFail();
        $now = new \DateTimeImmutable();

        $quest = $this->catalog->find($code);
        if (null === $quest) {
            throw new NotFoundHttpException(sprintf('Quête inconnue : %s', $code));
        }

        $periodKey = QuestCatalog::periodKey($quest['kind'], $now);
        if (null !== $this->completions->findOneForUserPeriod($user, $code, $periodKey)) {
            return new JsonResponse(['message' => 'Quête déjà réclamée'], Response::HTTP_CONFLICT);
        }

        $progress = $this->catalog->bestProgressFor($user, $quest, $now);
        if ($progress < $quest['target']) {
            return new JsonResponse([
                'message'  => 'Objectif non atteint',
                'progress' => $progress,
                'target'   => $quest['target'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $xpDelta = (int) round(
            $quest['xpReward'] * ProgressionPolicy::scoreMultiplier($user->getPrestigeLevel()),
        );

        $completion = new UserQuestCompletion($user, $code, $periodKey, $xpDelta);
        $user->addXp($xpDelta);
        $this->em->persist($completion);
        $this->em->flush();

        return new JsonResponse([
            'code'       => $code,
            'xpAwarded'  => $xpDelta,
            'totalXp'    => $user->getXp(),
            'level'      => ProgressionPolicy::levelFromXp($user->getXp()),
        ]);
    }

    private function getUserOrFail(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }
        return $user;
    }
}
