<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function loadFixtures(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $loader = $container->get(SymfonyFixturesLoader::class);
        $executor = new ORMExecutor($em, new ORMPurger($em));
        $executor->execute($loader->getFixtures(), false);
    }

    /**
     * Authenticate by calling /api/login_check and return a configured Client with the JWT bearer.
     */
    protected function clientFor(string $email, string $password): Client
    {
        $client = static::createClient();
        $client->request('POST', '/api/login_check', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['email' => $email, 'password' => $password]),
        ]);
        $data = $client->getResponse()->toArray();

        return static::createClient([], ['headers' => ['Authorization' => 'Bearer '.$data['token']]]);
    }

    protected function getUser(string $email): User
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);

        return $user;
    }
}
