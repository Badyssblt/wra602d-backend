<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds xp + prestige_level columns on app_user for the meta-progression system.
 */
final class Version20260518100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add xp and prestige_level columns to app_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD xp INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE app_user ADD prestige_level INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP COLUMN xp');
        $this->addSql('ALTER TABLE app_user DROP COLUMN prestige_level');
    }
}
