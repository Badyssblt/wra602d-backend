<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CityRepository;
use App\State\CitySaveProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ApiResource(
    shortName: 'City',
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(
            uriTemplate: '/cities/save',
            name: 'city_save',
            description: 'Sauvegarde / upsert une ville complète (grille + buildings + money)',
            processor: CitySaveProcessor::class,
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['city:write', 'building:write']],
            normalizationContext: ['groups' => ['city:read', 'building:read']],
        ),
        new Get(
            uriTemplate: '/cities/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(
            uriTemplate: '/cities/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
        new Delete(
            uriTemplate: '/cities/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
    ],
    normalizationContext: ['groups' => ['city:read', 'building:read']],
    denormalizationContext: ['groups' => ['city:write', 'building:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'user.uid' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'updatedAt'])]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 26, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['city:read', 'city:write', 'score:read'])]
    private string $uid;

    #[ORM\ManyToOne(inversedBy: 'cities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['city:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 80)]
    #[Groups(['city:read', 'city:write'])]
    private string $name = '';

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['city:read', 'city:write'])]
    private int $money = 0;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 4, max: 128)]
    #[Groups(['city:read', 'city:write'])]
    private int $gridSize = 12;

    /** @var list<array{tx: int, tz: int}> */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    #[Groups(['city:read', 'city:write'])]
    private array $unlockedTiles = [];

    /** @var Collection<int, Building> */
    #[ORM\OneToMany(mappedBy: 'city', targetEntity: Building::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['city:read', 'city:write'])]
    #[Assert\Valid]
    private Collection $buildings;

    #[ORM\Column]
    #[Groups(['city:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['city:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->uid = (new Ulid())->toBase32();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->buildings = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMoney(): int
    {
        return $this->money;
    }

    public function setMoney(int $money): static
    {
        $this->money = $money;

        return $this;
    }

    public function getGridSize(): int
    {
        return $this->gridSize;
    }

    public function setGridSize(int $gridSize): static
    {
        $this->gridSize = $gridSize;

        return $this;
    }

    /** @return list<array{tx: int, tz: int}> */
    public function getUnlockedTiles(): array
    {
        return $this->unlockedTiles;
    }

    /** @param list<array{tx: int, tz: int}> $tiles */
    public function setUnlockedTiles(array $tiles): static
    {
        $this->unlockedTiles = $tiles;

        return $this;
    }

    /** @return Collection<int, Building> */
    public function getBuildings(): Collection
    {
        return $this->buildings;
    }

    public function addBuilding(Building $building): static
    {
        if (!$this->buildings->contains($building)) {
            $this->buildings->add($building);
            $building->setCity($this);
        }

        return $this;
    }

    public function removeBuilding(Building $building): static
    {
        $this->buildings->removeElement($building);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
