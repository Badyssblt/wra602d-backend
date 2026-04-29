<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\GameScore;
use App\Entity\User;
use App\Notifier\MailerNotifierInterface;
use App\Repository\GameScoreRepository;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\AsEntityListener;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: GameScore::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: GameScore::class)]
final class GameScoreListener
{
    public function __construct(
        private readonly Security $security,
        private readonly MailerNotifierInterface $mailer,
        private readonly GameScoreRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function prePersist(GameScore $score, PrePersistEventArgs $event): void
    {
        if (null === $score->getUser()) {
            $current = $this->security->getUser();
            if ($current instanceof User) {
                $score->setUser($current);
            }
        }
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
