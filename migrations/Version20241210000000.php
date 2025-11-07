<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241210000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce vehicle types and features for transfers and drivers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vehicle_type (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) NOT NULL DEFAULT 1, orden SMALLINT NOT NULL DEFAULT 0, creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actualizado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_vehicle_type_nombre (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicle_feature (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(150) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) NOT NULL DEFAULT 1, creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actualizado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_vehicle_feature_nombre (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driver_profile_vehicle_feature (driver_profile_id INT NOT NULL, vehicle_feature_id INT NOT NULL, INDEX IDX_driver_profile_feature_driver (driver_profile_id), INDEX IDX_driver_profile_feature_feature (vehicle_feature_id), PRIMARY KEY(driver_profile_id, vehicle_feature_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE driver_profile ADD vehicle_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_request ADD vehicle_type_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_driver_profile_vehicle_type ON driver_profile (vehicle_type_id)');
        $this->addSql('CREATE INDEX IDX_transfer_request_vehicle_type ON transfer_request (vehicle_type_id)');
        $this->addSql('ALTER TABLE driver_profile ADD CONSTRAINT FK_driver_profile_vehicle_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_type (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_transfer_request_vehicle_type FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_type (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_profile_vehicle_feature ADD CONSTRAINT FK_driver_profile_feature_driver FOREIGN KEY (driver_profile_id) REFERENCES driver_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE driver_profile_vehicle_feature ADD CONSTRAINT FK_driver_profile_feature_feature FOREIGN KEY (vehicle_feature_id) REFERENCES vehicle_feature (id) ON DELETE CASCADE');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $defaults = [
            'Sedán',
            'SUV',
            'Van',
            'Minibús',
            'Premium',
        ];

        foreach ($defaults as $index => $nombre) {
            $this->addSql(
                'INSERT INTO vehicle_type (nombre, descripcion, activo, orden, creado_en, actualizado_en) VALUES (:nombre, NULL, 1, :orden, :now, :now)',
                ['nombre' => $nombre, 'orden' => $index, 'now' => $now],
                ['nombre' => Types::STRING, 'orden' => Types::INTEGER, 'now' => Types::DATETIME_IMMUTABLE]
            );
        }

        $this->addSql('UPDATE driver_profile dp JOIN vehicle_type vt ON LOWER(vt.nombre) = LOWER(dp.tipo_vehiculo) SET dp.vehicle_type_id = vt.id');
        $this->addSql('UPDATE transfer_request tr JOIN vehicle_type vt ON LOWER(vt.nombre) = LOWER(tr.tipo_vehiculo) SET tr.vehicle_type_id = vt.id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE driver_profile DROP FOREIGN KEY FK_driver_profile_vehicle_type');
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_transfer_request_vehicle_type');
        $this->addSql('ALTER TABLE driver_profile_vehicle_feature DROP FOREIGN KEY FK_driver_profile_feature_driver');
        $this->addSql('ALTER TABLE driver_profile_vehicle_feature DROP FOREIGN KEY FK_driver_profile_feature_feature');
        $this->addSql('DROP TABLE driver_profile_vehicle_feature');
        $this->addSql('DROP TABLE vehicle_feature');
        $this->addSql('DROP TABLE vehicle_type');
        $this->addSql('ALTER TABLE driver_profile DROP vehicle_type_id');
        $this->addSql('ALTER TABLE transfer_request DROP vehicle_type_id');
    }
}
