<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\BuildingRepository;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BuildingRepository::class)]
#[ORM\Table(name: 'building')]
#[ORM\UniqueConstraint(name: 'building_position_unique', columns: ['city_id', 'pos_x', 'pos_z'])]
#[ApiResource(
    shortName: 'Building',
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getCity().getUser() == user"),
    ],
    normalizationContext: ['groups' => ['building:read']],
    denormalizationContext: ['groups' => ['building:write']],
)]
#[CustomAssert\BuildingPositionWithinGrid]
class Building
{
    public const TYPES = ['house', 'office', 'industry', 'park', 'road'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'buildings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?City $city = null;

    #[ORM\Column(length: 16)]
    #[Assert\Choice(choices: self::TYPES)]
    #[Groups(['building:read', 'building:write', 'city:read', 'city:write'])]
    private string $type = 'house';

    #[ORM\Column(type: 'smallint')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(['building:read', 'building:write', 'city:read', 'city:write'])]
    private int $posX = 0;

    #[ORM\Column(type: 'smallint')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Groups(['building:read', 'building:write', 'city:read', 'city:write'])]
    private int $posZ = 0;

    #[ORM\Column]
    #[Groups(['building:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPosX(): int
    {
        return $this->posX;
    }

    public function setPosX(int $posX): static
    {
        $this->posX = $posX;

        return $this;
    }

    public function getPosZ(): int
    {
        return $this->posZ;
    }

    public function setPosZ(int $posZ): static
    {
        $this->posZ = $posZ;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
