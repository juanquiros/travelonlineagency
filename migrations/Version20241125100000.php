<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241125100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea las tablas de destinos turísticos y sus categorías';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE destino_categoria (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, icono VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE destino (id INT AUTO_INCREMENT NOT NULL, categoria_id INT NOT NULL, nombre VARCHAR(180) NOT NULL, direccion VARCHAR(255) NOT NULL, coordenadas_lat DOUBLE PRECISION DEFAULT NULL, coordenadas_lng DOUBLE PRECISION DEFAULT NULL, descripcion_corta VARCHAR(255) NOT NULL, descripcion_detallada LONGTEXT NOT NULL, imagen_principal VARCHAR(255) DEFAULT NULL, activo TINYINT(1) NOT NULL, INDEX IDX_2E3F6C4988CC3D7B (categoria_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE destino ADD CONSTRAINT FK_2E3F6C4988CC3D7B FOREIGN KEY (categoria_id) REFERENCES destino_categoria (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE destino DROP FOREIGN KEY FK_2E3F6C4988CC3D7B');
        $this->addSql('DROP TABLE destino');
        $this->addSql('DROP TABLE destino_categoria');
    }
}
