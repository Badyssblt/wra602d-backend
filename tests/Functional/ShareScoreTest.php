<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\GameScore;
use Doctrine\ORM\EntityManagerInterface;

final class ShareScoreTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testGenerateShareLinkAndAccessAnonymously(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->getUser('user1@local');
        $score = $em->getRepository(GameScore::class)->findOneBy(['user' => $user]);
        self::assertNotNull($score);

        $owner = $this->clientFor('user1@local', 'player1234');
        $owner->request('POST', sprintf('/api/scores/%s/share', $score->getUid()), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ]);
        self::assertResponseIsSuccessful();
        $payload = $owner->getResponse()->toArray();
        self::assertArrayHasKey('shareToken', $payload);
        self::assertArrayHasKey('shareUrl', $payload);

        $anon = static::createClient();
        $anon->request('GET', sprintf('/api/scores/share/%s', $payload['shareToken']));
        self::assertResponseIsSuccessful();
    }
}
