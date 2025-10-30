<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241205120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agregar datos de pasajeros, vuelo y código de servicio a transfer_request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_request ADD cantidad_pasajeros INT DEFAULT NULL, ADD vuelo_pasajero VARCHAR(50) DEFAULT NULL, ADD codigo_servicio VARCHAR(40) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10ACEB38B1B3B8E ON transfer_request (codigo_servicio)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_10ACEB38B1B3B8E ON transfer_request');
        $this->addSql('ALTER TABLE transfer_request DROP cantidad_pasajeros, DROP vuelo_pasajero, DROP codigo_servicio');
    }
}
