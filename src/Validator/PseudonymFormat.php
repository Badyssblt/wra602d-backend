<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class PseudonymFormat extends Constraint
{
    public string $message = 'Le pseudonyme "{{ value }}" doit contenir 3 à 30 caractères alphanumériques (underscore et tiret autorisés), sans espaces.';
}
