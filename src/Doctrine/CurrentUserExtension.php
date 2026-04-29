<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\City;
use App\Entity\GameScore;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const PUBLIC_OPERATIONS = ['leaderboard', 'shared_score', 'user_register'];

    private const SCOPED_RESOURCES = [GameScore::class, City::class];

    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->addWhere($queryBuilder, $resourceClass, $operation);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->addWhere($queryBuilder, $resourceClass, $operation);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass, ?Operation $operation): void
    {
        if (!\in_array($resourceClass, self::SCOPED_RESOURCES, true)) {
            return;
        }

        if (null !== $operation && \in_array($operation->getName(), self::PUBLIC_OPERATIONS, true)) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $alias = $qb->getRootAliases()[0];
        $qb->andWhere(sprintf('%s.user = :__current_user', $alias))
            ->setParameter('__current_user', $user);
    }
}
