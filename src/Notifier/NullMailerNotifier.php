<?php

declare(strict_types=1);

namespace App\Notifier;

use App\Entity\GameScore;
use App\Entity\User;

final class NullMailerNotifier implements MailerNotifierInterface
{
    /** @var list<array{type: string, to: string}> */
    public array $sent = [];

    public function sendBestScoreNotification(GameScore $score): void
    {
        $user = $score->getUser();
        if (null !== $user) {
            $this->sent[] = ['type' => 'new_high_score', 'to' => $user->getEmail()];
        }
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->sent[] = ['type' => 'welcome', 'to' => $user->getEmail()];
    }
}
