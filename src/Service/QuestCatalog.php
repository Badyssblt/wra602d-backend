<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\GameScoreRepository;

/**
 * Catalog of available quests + server-side validation of claim attempts.
 *
 * Quest metrics are derived from the player's submitted GameScores in the
 * relevant period (today for daily, this week for weekly). The client never
 * supplies "I achieved X" — the server is authoritative.
 */
final class QuestCatalog
{
    public const KIND_DAILY = 'daily';
    public const KIND_WEEKLY = 'weekly';

    public const METRIC_SCORE = 'score';
    public const METRIC_POPULATION = 'population';
    public const METRIC_TICKS = 'ticks';

    /**
     * Hardcoded catalog. Each entry must have:
     *  - code: stable identifier (snake_case, max 64)
     *  - label: human-readable, French
     *  - kind: daily/weekly
     *  - metric: which GameScore field to test against
     *  - target: minimum value to claim
     *  - xpReward: XP credited on successful claim
     *
     * @return list<array{code:string,label:string,kind:string,metric:string,target:int,xpReward:int}>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'daily_score_5k',
                'label' => 'Atteindre 5 000 points en une partie',
                'kind' => self::KIND_DAILY,
                'metric' => self::METRIC_SCORE,
                'target' => 5000,
                'xpReward' => 200,
            ],
            [
                'code' => 'daily_population_100',
                'label' => 'Atteindre 100 habitants',
                'kind' => self::KIND_DAILY,
                'metric' => self::METRIC_POPULATION,
                'target' => 100,
                'xpReward' => 200,
            ],
            [
                'code' => 'daily_survive_2m',
                'label' => 'Survivre 2 minutes (120 s)',
                'kind' => self::KIND_DAILY,
                'metric' => self::METRIC_TICKS,
                'target' => 120,
                'xpReward' => 150,
            ],
            [
                'code' => 'weekly_score_25k',
                'label' => 'Score hebdomadaire : 25 000 pts',
                'kind' => self::KIND_WEEKLY,
                'metric' => self::METRIC_SCORE,
                'target' => 25000,
                'xpReward' => 800,
            ],
        ];
    }

    /**
     * @return array{code:string,label:string,kind:string,metric:string,target:int,xpReward:int}|null
     */
    public function find(string $code): ?array
    {
        foreach ($this->all() as $quest) {
            if ($quest['code'] === $code) {
                return $quest;
            }
        }

        return null;
    }

    /** Builds the period key used to scope a completion record. */
    public static function periodKey(string $kind, \DateTimeImmutable $now): string
    {
        if (self::KIND_WEEKLY === $kind) {
            return $now->format('o-\WW'); // ISO week, e.g. "2026-W20"
        }

        return $now->format('Y-m-d');
    }

    public function __construct(private readonly GameScoreRepository $scores)
    {
    }

    /**
     * Returns the player's best value for the quest's metric within the relevant period.
     *
     * @param array<string, mixed> $quest
     */
    public function bestProgressFor(User $user, array $quest, \DateTimeImmutable $now): int
    {
        $since = self::KIND_WEEKLY === $quest['kind']
            ? $now->modify('monday this week')->setTime(0, 0)
            : $now->setTime(0, 0);

        $field = match ($quest['metric']) {
            self::METRIC_SCORE => 's.score',
            self::METRIC_POPULATION => 's.population',
            self::METRIC_TICKS => 's.ticksPlayed',
            default => 's.score',
        };

        /** @var numeric-string|float|int|null $best */
        $best = $this->scores->createQueryBuilder('s')
            ->select(sprintf('MAX(%s)', $field))
            ->andWhere('s.user = :user')
            ->andWhere('s.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $best ? 0 : (int) $best;
    }
}
