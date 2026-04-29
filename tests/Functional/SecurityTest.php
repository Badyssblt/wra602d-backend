<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class SecurityTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testNonAdminCannotListUsers(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('GET', '/api/users');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanListUsers(): void
    {
        $client = $this->clientFor('admin@local', 'admin1234');
        $client->request('GET', '/api/users');
        self::assertResponseIsSuccessful();
    }
}
