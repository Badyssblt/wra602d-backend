<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\City;
use App\Entity\GameScore;
use App\Entity\User;
use App\Repository\GameScoreRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Upserts the leaderboard GameScore for a user from the snapshot saved on
 * their City, and credits the corresponding XP delta to the user.
 *
 * Anti-farming rule: XP is credited only on the **positive delta** between
 * the new (prestige-multiplied) score and the user's previous best.
 * Re-saving the same city without improving the score yields 0 XP.
 *
 * The service is the single source of truth for score → leaderboard → XP.
 * It is invoked from CitySaveProcessor and stays trivially unit-testable.
 */
final readonly class ScoreUpsertService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameScoreRepository $scores,
    ) {
    }

    /**
     * @return array{xpAwarded: int, score: int, multipliedScore: int, isNewBest: bool}
     */
    public function applyCitySnapshot(User $user, City $city): array
    {
        $rawScore = $city->getScore();
        $multiplied = ProgressionPolicy::applyPrestigeToScore($rawScore, $user->getPrestigeLevel());

        $existing = $this->scores->findBestScoreForUser($user);
        $previousBest = $existing?->getScore() ?? 0;

        $isNewBest = $multiplied > $previousBest;
        $xpAwarded = 0;

        if ($isNewBest) {
            // XP from the delta only — re-saving the same city without progress yields 0 XP.
            $delta = $multiplied - $previousBest;
            $xpAwarded = ProgressionPolicy::xpForScore($delta);
            if ($xpAwarded > 0) {
                $user->addXp($xpAwarded);
            }

            // Upsert: update the user's existing GameScore (one per user in this model),
            // or create a new one. Either way the leaderboard sees one row per user.
            $target = $existing ?? new GameScore();
            $target->setUser($user);
            $target->setScore($multiplied);
            $target->setMoneyFinal(max(0, $city->getMoney()));
            $target->setPopulation($city->getPopulation());
            $target->setTicksPlayed($city->getTicksPlayed());
            $target->setCity($city);

            if (null === $existing) {
                $this->em->persist($target);
            }
        }

        $this->em->flush();

        return [
            'xpAwarded' => $xpAwarded,
            'score' => $rawScore,
            'multipliedScore' => $multiplied,
            'isNewBest' => $isNewBest,
        ];
    }
}
