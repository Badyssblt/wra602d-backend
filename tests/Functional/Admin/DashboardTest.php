<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\AbstractApiTestCase;

final class DashboardTest extends AbstractApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures();
    }

    public function testLoginPageRendered(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');
        self::assertResponseIsSuccessful();
    }

    public function testDashboardRequiresAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        self::assertResponseRedirects();
    }
}
