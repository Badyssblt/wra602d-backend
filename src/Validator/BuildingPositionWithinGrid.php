<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class BuildingPositionWithinGrid extends Constraint
{
    public string $message = 'La position ({{ x }}, {{ z }}) est hors de la grille {{ size }}x{{ size }}.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
