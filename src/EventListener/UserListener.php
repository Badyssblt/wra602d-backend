<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: User::class)]
final class UserListener
{
    public function __construct(
        private readonly \App\Notifier\MailerNotifierInterface $mailer,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        if ([] === $user->getRoles() || ['ROLE_USER'] === $user->getRoles()) {
            $user->setRoles(['ROLE_USER']);
        }
    }

    public function postPersist(User $user, PostPersistEventArgs $event): void
    {
        try {
            $this->mailer->sendWelcomeEmail($user);
        } catch (\Throwable $e) {
            $this->logger->error('Welcome email dispatch failed', ['error' => $e->getMessage()]);
        }
    }
}
