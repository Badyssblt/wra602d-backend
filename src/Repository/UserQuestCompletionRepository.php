<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserQuestCompletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserQuestCompletion>
 */
final class UserQuestCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserQuestCompletion::class);
    }

    /**
     * Returns the set of quest codes the user has already claimed for the given period.
     *
     * @param list<string> $questCodes
     * @return list<string>
     */
    public function findClaimedCodes(User $user, array $questCodes, string $periodKey): array
    {
        if ([] === $questCodes) {
            return [];
        }

        /** @var list<array{questCode: string}> $rows */
        $rows = $this->createQueryBuilder('c')
            ->select('c.questCode')
            ->andWhere('c.user = :user')
            ->andWhere('c.periodKey = :period')
            ->andWhere('c.questCode IN (:codes)')
            ->setParameter('user', $user)
            ->setParameter('period', $periodKey)
            ->setParameter('codes', $questCodes)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $r): string => (string) $r['questCode'], $rows);
    }

    public function findOneForUserPeriod(User $user, string $questCode, string $periodKey): ?UserQuestCompletion
    {
        return $this->findOneBy([
            'user' => $user,
            'questCode' => $questCode,
            'periodKey' => $periodKey,
        ]);
    }
}
