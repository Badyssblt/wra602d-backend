<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ScoreTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testLeaderboardIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/scores/leaderboard');
        self::assertResponseIsSuccessful();
    }

    public function testUserSeesOnlyOwnScores(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('GET', '/api/scores');
        self::assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        $items = $data['hydra:member'] ?? $data['member'] ?? [];
        self::assertNotEmpty($items);
        foreach ($items as $score) {
            self::assertSame('player1', $score['user']['pseudonym']);
        }
    }

    public function testCreateScore(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('POST', '/api/scores', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode([
                'score' => 9999,
                'moneyFinal' => 12345,
                'population' => 30,
                'ticksPlayed' => 100,
            ]),
        ]);
        self::assertResponseStatusCodeSame(201);
    }

    public function testRangeFilter(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('GET', '/api/scores?score[gt]=1000');
        self::assertResponseIsSuccessful();
    }
}
