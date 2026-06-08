<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Centralises XP / level / unlocks formulas.
 *
 * Keep it pure (no DB access, no dependencies) so it can be unit-tested in
 * isolation and reused by both the listener (XP credit) and User entity
 * (computed properties exposed via API).
 */
final class ProgressionPolicy
{
    /** Maximum reachable level before having to prestige. */
    public const MAX_LEVEL = 10;

    /** XP earned per 1000 (multiplied) score points. */
    public const XP_PER_KILO_SCORE = 5;

    /** Per-prestige-level multiplier bonus applied to XP gain. */
    public const PRESTIGE_XP_BONUS = 0.10;

    /** Curve coefficient: xpForLevel(n) = LEVEL_CURVE_COEFF · n². */
    private const LEVEL_CURVE_COEFF = 150;

    /**
     * XP threshold required to reach a given level (level >= 0).
     * Quadratic curve: see {@link self::LEVEL_CURVE_COEFF}.
     */
    public static function xpForLevel(int $level): int
    {
        $level = max(0, $level);

        return self::LEVEL_CURVE_COEFF * $level * $level;
    }

    /** Player level derived from accumulated XP, capped at MAX_LEVEL. */
    public static function levelFromXp(int $xp): int
    {
        $xp = max(0, $xp);
        $level = (int) floor(sqrt($xp / self::LEVEL_CURVE_COEFF));

        return min(self::MAX_LEVEL, $level);
    }

    /**
     * XP earned by submitting a score. The prestige bonus is already baked
     * into the stored score (applied in GameScoreListener::prePersist), so
     * this method only converts score → XP linearly.
     */
    public static function xpForScore(int $score): int
    {
        if ($score <= 0) {
            return 0;
        }

        return intdiv($score, 1000) * self::XP_PER_KILO_SCORE;
    }

    /**
     * Multiplied score = raw × (1 + 0.1 · prestigeLevel). Applied once at
     * submission so the leaderboard, the user's history and the XP all use
     * the same canonical value.
     */
    public static function applyPrestigeToScore(int $rawScore, int $prestigeLevel): int
    {
        if ($rawScore <= 0) {
            return 0;
        }

        return (int) floor($rawScore * self::scoreMultiplier($prestigeLevel));
    }

    /**
     * List of building types unlocked at a given level. Order matters for UI.
     *
     * @return list<string>
     */
    public static function unlockedBuildings(int $level): array
    {
        $level = max(0, $level);
        $unlocks = ['house', 'office', 'industry', 'park', 'road'];
        if ($level >= 3) {
            $unlocks[] = 'university';
        }
        if ($level >= 5) {
            $unlocks[] = 'powerplant';
        }
        if ($level >= 7) {
            $unlocks[] = 'port';
        }

        return $unlocks;
    }

    /**
     * Final score multiplier from prestige (applied on top of in-game score).
     * +10 % per prestige tier.
     */
    public static function scoreMultiplier(int $prestigeLevel): float
    {
        return 1.0 + max(0, $prestigeLevel) * self::PRESTIGE_XP_BONUS;
    }
}
