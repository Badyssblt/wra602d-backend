<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateAdminCommandTest extends KernelTestCase
{
    public function testCreatesAdminUser(): void
    {
        self::bootKernel();
        $app = new Application(self::$kernel);
        $cmd = $app->find('app:create-admin');
        $tester = new CommandTester($cmd);
        $tester->execute([
            'email' => 'newadmin@local',
            'pseudonym' => 'NewAdmin',
            '--password' => 'Sup3rSecret',
        ]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Admin créé', $tester->getDisplay());
    }

    public function testRejectsDuplicateEmail(): void
    {
        self::bootKernel();
        $app = new Application(self::$kernel);
        $cmd = $app->find('app:create-admin');
        $tester = new CommandTester($cmd);

        $tester->execute(['email' => 'dup@local', 'pseudonym' => 'dup1', '--password' => 'Sup3rSecret']);
        $exit = $tester->execute(['email' => 'dup@local', 'pseudonym' => 'dup2', '--password' => 'Sup3rSecret']);

        self::assertSame(1, $exit);
        self::assertStringContainsString('déjà utilisé', $tester->getDisplay());
    }
}
