<?php

declare(strict_types=1);

namespace App\Notifier;

use App\Entity\GameScore;
use App\Entity\User;

interface MailerNotifierInterface
{
    public function sendBestScoreNotification(GameScore $score): void;

    public function sendWelcomeEmail(User $user): void;
}
