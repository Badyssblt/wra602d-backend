<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\GameScore;
use App\Notifier\MailerNotifierInterface;
use App\Repository\GameScoreRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Sends the "new best score" mail when a freshly-inserted GameScore is the
 * user's best.
 *
 * Score/XP business logic lives in {@see App\Service\ScoreUpsertService}, which
 * is called from CitySaveProcessor — this listener stays a thin notifier.
 */
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: GameScore::class)]
final class GameScoreListener
{
    public function __construct(
        private readonly MailerNotifierInterface $mailer,
        private readonly GameScoreRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postPersist(GameScore $score, PostPersistEventArgs $event): void
    {
        $user = $score->getUser();
        if (null === $user) {
            return;
        }

        $best = $this->repository->findBestScoreForUser($user);
        if (null === $best || $best->getId() !== $score->getId()) {
            return;
        }

        try {
            $this->mailer->sendBestScoreNotification($score);
        } catch (\Throwable $e) {
            $this->logger->error('Best-score notification failed', ['error' => $e->getMessage()]);
        }
    }
}
