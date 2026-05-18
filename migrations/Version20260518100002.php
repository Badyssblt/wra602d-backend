<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds score / population / ticks_played columns on city. Save unifies score
 * submission, so the city now owns the canonical snapshot of the player's
 * progress; the GameScore is upserted from these values.
 */
final class Version20260518100002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add score, population, ticks_played columns to city';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE city ADD score INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE city ADD population INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE city ADD ticks_played INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE city DROP COLUMN score');
        $this->addSql('ALTER TABLE city DROP COLUMN population');
        $this->addSql('ALTER TABLE city DROP COLUMN ticks_played');
    }
}
