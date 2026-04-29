<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Building;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class BuildingPositionWithinGridValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BuildingPositionWithinGrid) {
            throw new UnexpectedTypeException($constraint, BuildingPositionWithinGrid::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Building) {
            throw new UnexpectedValueException($value, Building::class);
        }

        $city = $value->getCity();
        if (null === $city) {
            return;
        }

        $size = $city->getGridSize();
        $x = $value->getPosX();
        $z = $value->getPosZ();

        if ($x < 0 || $x >= $size || $z < 0 || $z >= $size) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ x }}', (string) $x)
                ->setParameter('{{ z }}', (string) $z)
                ->setParameter('{{ size }}', (string) $size)
                ->atPath('posX')
                ->addViolation();
        }
    }
}
