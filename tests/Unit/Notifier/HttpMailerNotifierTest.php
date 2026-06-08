<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifier;

use App\Entity\GameScore;
use App\Entity\User;
use App\Notifier\HttpMailerNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpMailerNotifierTest extends TestCase
{
    public function testWelcomeEmailDispatchesPostToMicroservice(): void
    {
        $captured = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? null];

            return new MockResponse('', ['http_code' => 202]);
        });
        $notifier = new HttpMailerNotifier($client, 'http://localhost:5173', new NullLogger());

        $user = new User()->setEmail('alice@example.com')->setPseudonym('alice');
        $notifier->sendWelcomeEmail($user);

        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertStringContainsString('/api/send-email', $captured['url']);
        self::assertIsString($captured['body']);
        $payload = json_decode($captured['body'], true);
        self::assertSame('alice@example.com', $payload['to']);
        self::assertSame('welcome', $payload['template']);
        self::assertSame('alice', $payload['context']['username']);
    }

    public function testSwallowsHttpFailures(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('boom', ['http_code' => 500]));
        $notifier = new HttpMailerNotifier($client, 'http://localhost:5173', new NullLogger());

        $score = new GameScore()
            ->setScore(100)
            ->setUser(new User()->setEmail('a@b.c')->setPseudonym('ab'));

        // Should not throw — fire-and-forget semantics.
        $notifier->sendBestScoreNotification($score);
        $this->expectNotToPerformAssertions();
    }
}
