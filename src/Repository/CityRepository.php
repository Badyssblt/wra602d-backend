<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
final class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findOneByUidAndUser(string $uid, User $user): ?City
    {
        return $this->findOneBy(['uid' => $uid, 'user' => $user]);
    }
}
