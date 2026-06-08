<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Api\ShareScoreController;
use App\Repository\GameScoreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameScoreRepository::class)]
#[ApiResource(
    shortName: 'Score',
    operations: [
        new GetCollection(
            uriTemplate: '/scores',
            description: 'Liste des scores du joueur courant',
            security: "is_granted('ROLE_USER')",
        ),
        new GetCollection(
            uriTemplate: '/scores/leaderboard',
            name: 'leaderboard',
            description: 'Classement public (top 50)',
            paginationItemsPerPage: 50,
            order: ['score' => 'DESC'],
            normalizationContext: ['groups' => ['score:read', 'score:public']],
        ),
        new Get(
            uriTemplate: '/scores/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
        new Get(
            uriTemplate: '/scores/share/{shareToken}',
            name: 'shared_score',
            description: 'Affichage public d’un score partagé',
            uriVariables: ['shareToken' => new \ApiPlatform\Metadata\Link(fromClass: GameScore::class, identifiers: ['shareToken'])],
            normalizationContext: ['groups' => ['score:read', 'score:public']],
        ),
        new Post(
            uriTemplate: '/scores/{uid}/share',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            name: 'score_share',
            controller: ShareScoreController::class,
            read: true,
            write: false,
            security: "is_granted('ROLE_USER') and object.getUser() == user",
            description: 'Génère un lien partageable pour ce score',
        ),
        new Delete(
            uriTemplate: '/scores/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
    ],
    normalizationContext: ['groups' => ['score:read']],
    denormalizationContext: ['groups' => ['score:write']],
    order: ['score' => 'DESC'],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: ['user.uid' => 'exact', 'user.pseudonym' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['score', 'createdAt'], arguments: ['orderParameterName' => 'order'])]
#[ApiFilter(RangeFilter::class, properties: ['score', 'population'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
class GameScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 26, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['score:read', 'score:public'])]
    private string $uid;

    #[ORM\ManyToOne(inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['score:read', 'score:public'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\NotNull, Assert\PositiveOrZero]
    #[Groups(['score:read', 'score:public', 'score:write'])]
    private int $score = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['score:read', 'score:write'])]
    private int $moneyFinal = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['score:read', 'score:public', 'score:write'])]
    private int $population = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['score:read', 'score:write'])]
    private int $ticksPlayed = 0;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['score:read', 'score:write'])]
    private ?City $city = null;

    #[ORM\Column(length: 26, unique: true, nullable: true)]
    #[Groups(['score:read'])]
    private ?string $shareToken = null;

    #[ORM\Column]
    #[Groups(['score:read', 'score:public'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->uid = new Ulid()->toBase32();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getMoneyFinal(): int
    {
        return $this->moneyFinal;
    }

    public function setMoneyFinal(int $moneyFinal): static
    {
        $this->moneyFinal = $moneyFinal;

        return $this;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function setPopulation(int $population): static
    {
        $this->population = $population;

        return $this;
    }

    public function getTicksPlayed(): int
    {
        return $this->ticksPlayed;
    }

    public function setTicksPlayed(int $ticksPlayed): static
    {
        $this->ticksPlayed = $ticksPlayed;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    public function setShareToken(?string $shareToken): static
    {
        $this->shareToken = $shareToken;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
