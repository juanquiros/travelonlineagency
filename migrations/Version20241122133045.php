<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122133045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow storing large Mercado Pago user identifiers as strings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credenciales_mercado_pago CHANGE user_id user_id VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credenciales_mercado_pago CHANGE user_id user_id INT DEFAULT NULL');
    }
}
