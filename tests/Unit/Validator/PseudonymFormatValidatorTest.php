<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\PseudonymFormat;
use App\Validator\PseudonymFormatValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PseudonymFormatValidator>
 */
final class PseudonymFormatValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PseudonymFormatValidator
    {
        return new PseudonymFormatValidator();
    }

    public function testNullAndEmptyAreSkipped(): void
    {
        $this->validator->validate(null, new PseudonymFormat());
        $this->assertNoViolation();

        $this->validator->validate('', new PseudonymFormat());
        $this->assertNoViolation();
    }

    /** @return iterable<string, array{string}> */
    public static function validPseudonyms(): iterable
    {
        yield 'short' => ['abc'];
        yield 'with underscore' => ['John_Doe'];
        yield 'mixed' => ['x-y_2'];
        yield 'max length' => [str_repeat('A', 30)];
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPseudonyms(): iterable
    {
        yield 'too short' => ['ab'];
        yield 'with space' => ['with space'];
        yield 'unicode' => ['étoile'];
        yield 'one char' => ['x'];
        yield 'over max' => [str_repeat('A', 31)];
    }

    #[DataProvider('validPseudonyms')]
    public function testValidPseudonymsPass(string $value): void
    {
        $this->validator->validate($value, new PseudonymFormat());
        $this->assertNoViolation();
    }

    #[DataProvider('invalidPseudonyms')]
    public function testInvalidPseudonymsAreFlagged(string $value): void
    {
        $constraint = new PseudonymFormat();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->assertRaised();
    }
}
