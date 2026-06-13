<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\City;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserRegisterProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<User, User> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $hasher,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (null !== $data->getPassword()) {
            $data->setPassword($this->hasher->hashPassword($data, $data->getPassword()));
        }
        if ([] === $data->getRoles() || ['ROLE_USER'] === $data->getRoles()) {
            $data->setRoles(['ROLE_USER']);
        }

        /** @var User $persisted */
        $persisted = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        $cityName = trim((string) $data->getCityName());
        if ($cityName !== '') {
            $city = new City();
            $city->setUser($persisted);
            $city->setName(substr($cityName, 0, 80));
            $city->setMoney(50_000);
            $city->setGridSize(48);
            $this->em->persist($city);
            $this->em->flush();
        }

        return $persisted;
    }
}
