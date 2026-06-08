<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameScore;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameScore>
 */
final class GameScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameScore::class);
    }

    public function findBestScoreForUser(User $user): ?GameScore
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.score', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GameScore>
     */
    public function findTopN(int $n): array
    {
        /** @var list<GameScore> $rows */
        $rows = $this->createQueryBuilder('s')
            ->orderBy('s.score', 'DESC')
            ->setMaxResults($n)
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return list<array{day: string, count: int}>
     */
    public function countByDayLast7(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<'SQL'
                SELECT TO_CHAR(DATE(created_at), 'YYYY-MM-DD') AS day, COUNT(*) AS count
                FROM game_score
                WHERE created_at >= NOW() - INTERVAL '7 days'
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC
            SQL;
        /** @var list<array{day: string, count: string|int}> $rows */
        $rows = $conn->executeQuery($sql)->fetchAllAssociative();

        return array_map(static fn (array $r): array => ['day' => (string) $r['day'], 'count' => (int) $r['count']], $rows);
    }

    public function getAverageScore(): float
    {
        /** @var numeric-string|float|int|null $avg */
        $avg = $this->createQueryBuilder('s')
            ->select('AVG(s.score)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $avg ? 0.0 : (float) $avg;
    }
}
