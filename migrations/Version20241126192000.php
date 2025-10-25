<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional logo column to transfer destinations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination ADD logo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination DROP logo');
    }
}

