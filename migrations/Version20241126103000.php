<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extiende transfer_destination con datos geográficos, categorías e imagen principal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE transfer_destination_category (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, icono VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transfer_destination ADD direccion VARCHAR(255) DEFAULT NULL, ADD coordenadas_lat DOUBLE PRECISION DEFAULT NULL, ADD coordenadas_lng DOUBLE PRECISION DEFAULT NULL, ADD categoria_id INT DEFAULT NULL, ADD descripcion_corta VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE transfer_destination SET coordenadas_lat = JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.lat")), coordenadas_lng = JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.lng")) WHERE metadata IS NOT NULL');
        $this->addSql('ALTER TABLE transfer_destination ADD CONSTRAINT FK_7D074A4488CC3D7B FOREIGN KEY (categoria_id) REFERENCES transfer_destination_category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7D074A4488CC3D7B ON transfer_destination (categoria_id)');
        $this->addSql('ALTER TABLE transfer_destination DROP metadata');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination ADD metadata JSON DEFAULT NULL');
        $this->addSql('UPDATE transfer_destination SET metadata = JSON_OBJECT("lat", coordenadas_lat, "lng", coordenadas_lng) WHERE coordenadas_lat IS NOT NULL AND coordenadas_lng IS NOT NULL');
        $this->addSql('ALTER TABLE transfer_destination DROP FOREIGN KEY FK_7D074A4488CC3D7B');
        $this->addSql('DROP INDEX IDX_7D074A4488CC3D7B ON transfer_destination');
        $this->addSql('ALTER TABLE transfer_destination DROP direccion, DROP coordenadas_lat, DROP coordenadas_lng, DROP categoria_id, DROP descripcion_corta');
        $this->addSql('DROP TABLE transfer_destination_category');
    }
}
