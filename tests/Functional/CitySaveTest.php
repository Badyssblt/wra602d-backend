<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final class CitySaveTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testSaveAndReplaceBuildings(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $uid = new Ulid()->toBase32();

        $payload = [
            'uid' => $uid,
            'name' => 'TestTown',
            'money' => 50000,
            'gridSize' => 12,
            'buildings' => [
                ['type' => 'house', 'posX' => 1, 'posZ' => 1],
                ['type' => 'road', 'posX' => 2, 'posZ' => 1],
            ],
        ];

        $client->request('POST', '/api/cities/save', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        self::assertResponseStatusCodeSame(201);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $city = $em->getRepository(City::class)->findOneBy(['uid' => $uid]);
        self::assertNotNull($city);
        self::assertCount(2, $city->getBuildings());

        $payload['buildings'] = [
            ['type' => 'office', 'posX' => 5, 'posZ' => 5],
        ];
        $client->request('POST', '/api/cities/save', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        self::assertResponseStatusCodeSame(201);

        $em->clear();
        $city = $em->getRepository(City::class)->findOneBy(['uid' => $uid]);
        self::assertCount(1, $city->getBuildings());
    }

    public function testInvalidPositionRejected(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('POST', '/api/cities/save', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode([
                'uid' => new Ulid()->toBase32(),
                'name' => 'BadTown',
                'money' => 0,
                'gridSize' => 12,
                'buildings' => [['type' => 'house', 'posX' => 12, 'posZ' => 0]],
            ]),
        ]);
        self::assertResponseStatusCodeSame(422);
    }
}
