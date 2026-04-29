<?php

declare(strict_types=1);

namespace App\Notifier;

use App\Entity\GameScore;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HttpMailerNotifier implements MailerNotifierInterface
{
    public function __construct(
        #[Autowire(service: 'mailer.client')]
        private HttpClientInterface $client,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
        private LoggerInterface $logger,
    ) {
    }

    public function sendBestScoreNotification(GameScore $score): void
    {
        $user = $score->getUser();
        if (null === $user) {
            return;
        }
        $this->dispatch(
            $user->getEmail(),
            'Nouveau record !',
            'new_high_score',
            [
                'username' => $user->getPseudonym(),
                'score' => $score->getScore(),
                'rank' => 1,
                'cityName' => $score->getCity()?->getName() ?? '—',
            ],
        );
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->dispatch(
            $user->getEmail(),
            'Bienvenue dans WRA602 City Builder !',
            'welcome',
            [
                'username' => $user->getPseudonym(),
                'frontendUrl' => $this->frontendUrl,
            ],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dispatch(string $to, string $subject, string $template, array $context): void
    {
        try {
            $this->client->request('POST', '/api/send-email', [
                'json' => [
                    'to' => $to,
                    'subject' => $subject,
                    'template' => $template,
                    'context' => $context,
                ],
            ])->getStatusCode();
        } catch (ExceptionInterface $e) {
            $this->logger->error('Microservice mailer call failed', [
                'template' => $template,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
