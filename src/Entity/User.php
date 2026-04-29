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
use App\Repository\UserRepository;
use App\State\MeProvider;
use App\State\UserRegisterProcessor;
use App\Validator as CustomAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['pseudonym'], message: 'Ce pseudonyme est déjà pris.')]
#[ApiResource(
    shortName: 'User',
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(
            uriTemplate: '/users/me',
            name: 'user_me',
            provider: MeProvider::class,
            security: "is_granted('ROLE_USER')",
            description: 'Retourne l’utilisateur courant',
        ),
        new Get(
            uriTemplate: '/users/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object == user",
        ),
        new Post(
            uriTemplate: '/users/register',
            name: 'user_register',
            processor: UserRegisterProcessor::class,
            denormalizationContext: ['groups' => ['user:write', 'user:register']],
            validationContext: ['groups' => ['Default', 'user:register']],
            description: 'Inscription publique',
        ),
        new Patch(
            uriTemplate: '/users/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN') or object == user",
        ),
        new Delete(
            uriTemplate: '/users/{uid}',
            requirements: ['uid' => '[A-Z0-9]{26}'],
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'partial', 'pseudonym' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'pseudonym'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 26, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['user:read', 'score:read', 'score:public', 'city:read'])]
    private string $uid;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank, Assert\Email]
    #[Groups(['user:read', 'user:write', 'user:register'])]
    private string $email = '';

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    #[CustomAssert\PseudonymFormat]
    #[Groups(['user:read', 'user:write', 'user:register', 'score:read', 'score:public'])]
    private string $pseudonym = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(groups: ['user:register'])]
    #[Assert\Length(min: 8, max: 64, groups: ['user:register'])]
    #[Assert\Regex(pattern: '/[0-9]/', message: 'Au moins un chiffre.', groups: ['user:register'])]
    #[Groups(['user:write', 'user:register'])]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, GameScore> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: GameScore::class, orphanRemoval: true)]
    private Collection $scores;

    /** @var Collection<int, City> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: City::class, orphanRemoval: true)]
    private Collection $cities;

    public function __construct()
    {
        $this->uid = (new Ulid())->toBase32();
        $this->createdAt = new \DateTimeImmutable();
        $this->scores = new ArrayCollection();
        $this->cities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPseudonym(): string
    {
        return $this->pseudonym;
    }

    public function setPseudonym(string $pseudonym): static
    {
        $this->pseudonym = $pseudonym;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, GameScore> */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    /** @return Collection<int, City> */
    public function getCities(): Collection
    {
        return $this->cities;
    }
}
