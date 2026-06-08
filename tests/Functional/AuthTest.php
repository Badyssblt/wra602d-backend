<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AuthTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testRegisterPublicEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/users/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'email' => 'newbie@local.test',
                'pseudonym' => 'newbie',
                'password' => 'Welcome1!',
            ]),
        ]);
        self::assertResponseStatusCodeSame(201);
    }

    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login_check', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['email' => 'admin@local', 'password' => 'admin1234']),
        ]);
        self::assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        self::assertArrayHasKey('token', $data);
    }

    public function testMeRoute(): void
    {
        $client = $this->clientFor('user1@local', 'player1234');
        $client->request('GET', '/api/users/me');
        self::assertResponseIsSuccessful();
        $data = $client->getResponse()->toArray();
        self::assertSame('player1', $data['pseudonym']);
    }

    public function testMeRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/me');
        self::assertResponseStatusCodeSame(401);
    }
}
