<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserRegisterProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof User) {
            throw new \LogicException('Expected User entity.');
        }
        if (null !== $data->getPassword()) {
            $data->setPassword($this->hasher->hashPassword($data, $data->getPassword()));
        }
        if ([] === $data->getRoles() || ['ROLE_USER'] === $data->getRoles()) {
            $data->setRoles(['ROLE_USER']);
        }

        /** @var User $persisted */
        $persisted = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $persisted;
    }
}
