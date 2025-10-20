<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cover image to transfer destinations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination ADD imagen_portada VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination DROP imagen_portada');
    }
}
