<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserQuestCompletionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Records that a given user has claimed a quest for a given period
 * (e.g. "daily_score" on 2026-05-18). The (user, questCode, periodKey)
 * triple is unique so claims cannot be replayed within the same period.
 */
#[ORM\Entity(repositoryClass: UserQuestCompletionRepository::class)]
#[ORM\Table(name: 'user_quest_completion')]
#[ORM\UniqueConstraint(name: 'uq_user_quest_period', columns: ['user_id', 'quest_code', 'period_key'])]
class UserQuestCompletion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $questCode;

    /** YYYY-MM-DD for daily quests, YYYY-Www for weekly. */
    #[ORM\Column(length: 16)]
    private string $periodKey;

    #[ORM\Column]
    private int $xpAwarded = 0;

    #[ORM\Column]
    private \DateTimeImmutable $claimedAt;

    public function __construct(User $user, string $questCode, string $periodKey, int $xpAwarded)
    {
        $this->user = $user;
        $this->questCode = $questCode;
        $this->periodKey = $periodKey;
        $this->xpAwarded = $xpAwarded;
        $this->claimedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getQuestCode(): string
    {
        return $this->questCode;
    }

    public function getPeriodKey(): string
    {
        return $this->periodKey;
    }

    public function getXpAwarded(): int
    {
        return $this->xpAwarded;
    }

    public function getClaimedAt(): \DateTimeImmutable
    {
        return $this->claimedAt;
    }
}
