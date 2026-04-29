<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Building;
use App\Entity\City;
use App\Validator\BuildingPositionWithinGrid;
use App\Validator\BuildingPositionWithinGridValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<BuildingPositionWithinGridValidator>
 */
final class BuildingPositionWithinGridValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): BuildingPositionWithinGridValidator
    {
        return new BuildingPositionWithinGridValidator();
    }

    public function testNullSkips(): void
    {
        $this->validator->validate(null, new BuildingPositionWithinGrid());
        $this->assertNoViolation();
    }

    public function testInsideGridIsValid(): void
    {
        $b = $this->makeBuilding(5, 7, 12);
        $this->validator->validate($b, new BuildingPositionWithinGrid());
        $this->assertNoViolation();
    }

    public function testOutsideGridIsInvalid(): void
    {
        $b = $this->makeBuilding(12, 0, 12);
        $constraint = new BuildingPositionWithinGrid();
        $this->validator->validate($b, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameter('{{ x }}', '12')
            ->setParameter('{{ z }}', '0')
            ->setParameter('{{ size }}', '12')
            ->atPath('property.path.posX')
            ->assertRaised();
    }

    public function testNegativePositionIsInvalid(): void
    {
        $b = $this->makeBuilding(0, -1, 12);
        $constraint = new BuildingPositionWithinGrid();
        $this->validator->validate($b, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameter('{{ x }}', '0')
            ->setParameter('{{ z }}', '-1')
            ->setParameter('{{ size }}', '12')
            ->atPath('property.path.posX')
            ->assertRaised();
    }

    private function makeBuilding(int $x, int $z, int $gridSize): Building
    {
        $city = new City();
        $city->setGridSize($gridSize);
        $b = new Building();
        $b->setCity($city);
        $b->setPosX($x);
        $b->setPosZ($z);

        return $b;
    }
}
