<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle type to drivers, testimonial fields to transfer requests, featured destinations, and transfer showcase pieces.';
    }

    public function up(Schema $schema): void
    {
        // driver profile vehicle type
        $this->addSql("ALTER TABLE driver_profile ADD tipo_vehiculo VARCHAR(100) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE driver_profile SET tipo_vehiculo = '' WHERE tipo_vehiculo IS NULL");
        $this->addSql('ALTER TABLE driver_profile ALTER COLUMN tipo_vehiculo DROP DEFAULT');

        // transfer request testimonial fields
        $this->addSql('ALTER TABLE transfer_request ADD tipo_vehiculo VARCHAR(100) DEFAULT NULL, ADD calificacion INT DEFAULT NULL, ADD testimonio_comentario LONGTEXT DEFAULT NULL, ADD testimonio_creado_en DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // featured destination flags
        $this->addSql('ALTER TABLE transfer_destination ADD destacado_inicio TINYINT(1) DEFAULT 0 NOT NULL, ADD orden_destacado SMALLINT DEFAULT 0 NOT NULL');

        // transfer showcase
        $this->addSql('CREATE TABLE transfer_showcase (id INT AUTO_INCREMENT NOT NULL, titulo VARCHAR(180) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, tipo VARCHAR(10) NOT NULL, imagen VARCHAR(255) DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, video_embed LONGTEXT DEFAULT NULL, destacado TINYINT(1) DEFAULT 0 NOT NULL, posicion SMALLINT DEFAULT 0 NOT NULL, creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actualizado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transfer_showcase');
        $this->addSql('ALTER TABLE transfer_destination DROP destacado_inicio, DROP orden_destacado');
        $this->addSql('ALTER TABLE transfer_request DROP tipo_vehiculo, DROP calificacion, DROP testimonio_comentario, DROP testimonio_creado_en');
        $this->addSql('ALTER TABLE driver_profile DROP tipo_vehiculo');
    }
}
