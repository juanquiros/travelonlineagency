<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional color reference to transfer destination categories.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination_category ADD color VARCHAR(9) DEFAULT NULL');
        $this->addSql("UPDATE transfer_destination_category SET color = '#1F6BB3' WHERE color IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination_category DROP color');
    }
}

