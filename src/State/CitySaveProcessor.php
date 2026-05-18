<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Building;
use App\Entity\City;
use App\Entity\User;
use App\Repository\CityRepository;
use App\Service\ScoreUpsertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<City, City>
 */
final readonly class CitySaveProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private CityRepository $cityRepo,
        private ScoreUpsertService $scoreUpsert,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): City
    {
        if (!$data instanceof City) {
            throw new \LogicException('Expected City entity.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $existing = $this->cityRepo->findOneByUidAndUser($data->getUid(), $user);
        $city = $existing ?? $data;
        $city->setUser($user);
        $city->setUpdatedAt(new \DateTimeImmutable());

        if (null !== $existing) {
            $existing->setName($data->getName());
            $existing->setMoney($data->getMoney());
            $existing->setGridSize($data->getGridSize());
            $existing->setUnlockedTiles($data->getUnlockedTiles());
            $existing->setScore($data->getScore());
            $existing->setPopulation($data->getPopulation());
            $existing->setTicksPlayed($data->getTicksPlayed());
            foreach ($existing->getBuildings()->toArray() as $b) {
                $existing->removeBuilding($b);
                $this->em->remove($b);
            }
            $this->em->flush();
            foreach ($data->getBuildings() as $b) {
                $clone = (new Building())
                    ->setType($b->getType())
                    ->setPosX($b->getPosX())
                    ->setPosZ($b->getPosZ());
                $existing->addBuilding($clone);
                $this->em->persist($clone);
            }
        } else {
            foreach ($city->getBuildings() as $b) {
                $b->setCity($city);
            }
            $this->em->persist($city);
        }

        $this->em->flush();

        // Upsert the leaderboard GameScore + credit XP delta. Anti-farming
        // is enforced inside the service (XP only on a new best).
        $this->scoreUpsert->applyCitySnapshot($user, $city);

        return $city;
    }
}
