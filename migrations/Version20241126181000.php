<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce tabla bootstrap_icon y relación opcional con categorías de destinos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bootstrap_icon (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, css_class VARCHAR(120) NOT NULL, UNIQUE INDEX UNIQ_7C689E74F83BD6E6 (css_class), UNIQUE INDEX UNIQ_7C689E74FCF8192D (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transfer_destination_category ADD bootstrap_icon_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_destination_category ADD CONSTRAINT FK_8BE64EE198C70F8 FOREIGN KEY (bootstrap_icon_id) REFERENCES bootstrap_icon (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8BE64EE198C70F8 ON transfer_destination_category (bootstrap_icon_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination_category DROP FOREIGN KEY FK_8BE64EE198C70F8');
        $this->addSql('DROP INDEX IDX_8BE64EE198C70F8 ON transfer_destination_category');
        $this->addSql('ALTER TABLE transfer_destination_category DROP bootstrap_icon_id');
        $this->addSql('DROP TABLE bootstrap_icon');
    }
}
