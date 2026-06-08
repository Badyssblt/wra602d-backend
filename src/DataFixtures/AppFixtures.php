<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Building;
use App\Entity\City;
use App\Entity\GameScore;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@local');
        $admin->setPseudonym('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        for ($i = 1; $i <= 5; ++$i) {
            $user = new User();
            $user->setEmail(sprintf('user%d@local', $i));
            $user->setPseudonym(sprintf('player%d', $i));
            $user->setPassword($this->hasher->hashPassword($user, 'player1234'));
            $manager->persist($user);

            $city = new City();
            $city->setUser($user);
            $city->setName(sprintf('Town-%d', $i));
            $city->setMoney(50_000 + $i * 1000);
            $manager->persist($city);

            for ($k = 0; $k < 4; ++$k) {
                $b = new Building();
                $b->setCity($city);
                $b->setType(['house', 'office', 'park', 'road'][$k]);
                $b->setPosX($k);
                $b->setPosZ(0);
                $manager->persist($b);
            }

            for ($s = 0; $s < 3; ++$s) {
                $score = new GameScore();
                $score->setUser($user);
                $score->setScore(1000 * $i + 100 * $s);
                $score->setMoneyFinal(40_000 + $s * 5000);
                $score->setPopulation(20 + $s);
                $score->setTicksPlayed(50 + $s * 10);
                if (0 === $s) {
                    $score->setCity($city);
                }
                $manager->persist($score);
            }
        }

        $manager->flush();
    }
}
