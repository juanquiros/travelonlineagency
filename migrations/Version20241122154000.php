<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create transfer management tables for destinations, combos, requests, assignments and driver profiles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE transfer_destination (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, tarifa_base NUMERIC(10, 2) NOT NULL, activo TINYINT(1) NOT NULL, metadata JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_combo (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, precio NUMERIC(10, 2) NOT NULL, activo TINYINT(1) NOT NULL, imagen_portada VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_combo_destination (id INT AUTO_INCREMENT NOT NULL, combo_id INT NOT NULL, destino_id INT NOT NULL, posicion INT NOT NULL, INDEX IDX_491035E21E5D0459 (combo_id), INDEX IDX_491035E253F7C8C (destino_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_form_field (id INT AUTO_INCREMENT NOT NULL, clave VARCHAR(100) NOT NULL, etiqueta VARCHAR(255) NOT NULL, tipo VARCHAR(30) NOT NULL, requerido TINYINT(1) NOT NULL, opciones JSON DEFAULT NULL, orden INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_request (id INT AUTO_INCREMENT NOT NULL, combo_id INT DEFAULT NULL, usuario_id INT DEFAULT NULL, tipo VARCHAR(20) NOT NULL, precio_total NUMERIC(10, 2) NOT NULL, moneda VARCHAR(3) NOT NULL, nombre_pasajero VARCHAR(150) NOT NULL, email_pasajero VARCHAR(150) NOT NULL, telefono_pasajero VARCHAR(50) DEFAULT NULL, arribo DATETIME DEFAULT NULL, salida DATETIME DEFAULT NULL, estado VARCHAR(20) NOT NULL, datos_extra JSON DEFAULT NULL, token_seguimiento VARCHAR(64) DEFAULT NULL, notas_cliente LONGTEXT DEFAULT NULL, creado_en DATETIME NOT NULL, actualizado_en DATETIME NOT NULL, INDEX IDX_10ACEB382BCFB96C (combo_id), INDEX IDX_10ACEB38DB38439E (usuario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_request_destination (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, destino_id INT NOT NULL, posicion INT NOT NULL, INDEX IDX_6B1100F9AB664AAF (solicitud_id), INDEX IDX_6B1100F953F7C8C (destino_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_request_field_value (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, campo_id INT NOT NULL, valor LONGTEXT DEFAULT NULL, INDEX IDX_34153339AB664AAF (solicitud_id), INDEX IDX_34153339EBC3E188 (campo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driver_profile (id INT AUTO_INCREMENT NOT NULL, usuario_id INT NOT NULL, nombre_completo VARCHAR(150) NOT NULL, documento VARCHAR(50) NOT NULL, telefono VARCHAR(80) NOT NULL, patente VARCHAR(120) NOT NULL, modelo_vehiculo VARCHAR(120) NOT NULL, foto_vehiculo VARCHAR(255) DEFAULT NULL, aprobado TINYINT(1) NOT NULL, notas LONGTEXT DEFAULT NULL, creado_en DATETIME NOT NULL, actualizado_en DATETIME NOT NULL, UNIQUE INDEX UNIQ_3A137CA3DB38439E (usuario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_assignment (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, chofer_id INT NOT NULL, estado VARCHAR(20) NOT NULL, parada_actual INT NOT NULL, notas LONGTEXT DEFAULT NULL, creado_en DATETIME NOT NULL, actualizado_en DATETIME NOT NULL, finalizado_en DATETIME DEFAULT NULL, INDEX IDX_89F3ECE3AB664AAF (solicitud_id), INDEX IDX_89F3ECE3C793D70 (chofer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE transfer_combo_destination ADD CONSTRAINT FK_491035E21E5D0459 FOREIGN KEY (combo_id) REFERENCES transfer_combo (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfer_combo_destination ADD CONSTRAINT FK_491035E253F7C8C FOREIGN KEY (destino_id) REFERENCES transfer_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_10ACEB382BCFB96C FOREIGN KEY (combo_id) REFERENCES transfer_combo (id)');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_10ACEB38DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE transfer_request_destination ADD CONSTRAINT FK_6B1100F9AB664AAF FOREIGN KEY (solicitud_id) REFERENCES transfer_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfer_request_destination ADD CONSTRAINT FK_6B1100F953F7C8C FOREIGN KEY (destino_id) REFERENCES transfer_destination (id)');
        $this->addSql('ALTER TABLE transfer_request_field_value ADD CONSTRAINT FK_34153339AB664AAF FOREIGN KEY (solicitud_id) REFERENCES transfer_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfer_request_field_value ADD CONSTRAINT FK_34153339EBC3E188 FOREIGN KEY (campo_id) REFERENCES transfer_form_field (id)');
        $this->addSql('ALTER TABLE driver_profile ADD CONSTRAINT FK_3A137CA3DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE transfer_assignment ADD CONSTRAINT FK_89F3ECE3AB664AAF FOREIGN KEY (solicitud_id) REFERENCES transfer_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfer_assignment ADD CONSTRAINT FK_89F3ECE3C793D70 FOREIGN KEY (chofer_id) REFERENCES driver_profile (id)');
        $this->addSql('ALTER TABLE mercado_pago_pago ADD transfer_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mercado_pago_pago ADD CONSTRAINT FK_A74A2EDC19613132 FOREIGN KEY (transfer_request_id) REFERENCES transfer_request (id)');
        $this->addSql('CREATE INDEX IDX_A74A2EDC19613132 ON mercado_pago_pago (transfer_request_id)');
        $this->addSql('ALTER TABLE pay_pal_pago ADD transfer_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pay_pal_pago ADD CONSTRAINT FK_907C343219613132 FOREIGN KEY (transfer_request_id) REFERENCES transfer_request (id)');
        $this->addSql('CREATE INDEX IDX_907C343219613132 ON pay_pal_pago (transfer_request_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mercado_pago_pago DROP FOREIGN KEY FK_A74A2EDC19613132');
        $this->addSql('DROP INDEX IDX_A74A2EDC19613132 ON mercado_pago_pago');
        $this->addSql('ALTER TABLE mercado_pago_pago DROP transfer_request_id');
        $this->addSql('ALTER TABLE pay_pal_pago DROP FOREIGN KEY FK_907C343219613132');
        $this->addSql('DROP INDEX IDX_907C343219613132 ON pay_pal_pago');
        $this->addSql('ALTER TABLE pay_pal_pago DROP transfer_request_id');
        $this->addSql('ALTER TABLE transfer_assignment DROP FOREIGN KEY FK_89F3ECE3AB664AAF');
        $this->addSql('ALTER TABLE transfer_assignment DROP FOREIGN KEY FK_89F3ECE3C793D70');
        $this->addSql('ALTER TABLE transfer_combo_destination DROP FOREIGN KEY FK_491035E21E5D0459');
        $this->addSql('ALTER TABLE transfer_combo_destination DROP FOREIGN KEY FK_491035E253F7C8C');
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_10ACEB382BCFB96C');
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_10ACEB38DB38439E');
        $this->addSql('ALTER TABLE transfer_request_destination DROP FOREIGN KEY FK_6B1100F9AB664AAF');
        $this->addSql('ALTER TABLE transfer_request_destination DROP FOREIGN KEY FK_6B1100F953F7C8C');
        $this->addSql('ALTER TABLE transfer_request_field_value DROP FOREIGN KEY FK_34153339AB664AAF');
        $this->addSql('ALTER TABLE transfer_request_field_value DROP FOREIGN KEY FK_34153339EBC3E188');
        $this->addSql('ALTER TABLE driver_profile DROP FOREIGN KEY FK_3A137CA3DB38439E');
        $this->addSql('DROP TABLE transfer_destination');
        $this->addSql('DROP TABLE transfer_combo');
        $this->addSql('DROP TABLE transfer_combo_destination');
        $this->addSql('DROP TABLE transfer_form_field');
        $this->addSql('DROP TABLE transfer_request');
        $this->addSql('DROP TABLE transfer_request_destination');
        $this->addSql('DROP TABLE transfer_request_field_value');
        $this->addSql('DROP TABLE driver_profile');
        $this->addSql('DROP TABLE transfer_assignment');
    }
}
