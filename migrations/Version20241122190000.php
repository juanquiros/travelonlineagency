<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch default platform branding assets to SVG variants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE plataforma SET logo = 'logo-toa.svg' WHERE logo = 'logo-toa.png'");
        $this->addSql("UPDATE plataforma SET icono = 'favicon-toa.svg' WHERE icono = 'favicon-toa.png'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE plataforma SET logo = 'logo-toa.png' WHERE logo = 'logo-toa.svg'");
        $this->addSql("UPDATE plataforma SET icono = 'favicon-toa.png' WHERE icono = 'favicon-toa.svg'");
    }
}
