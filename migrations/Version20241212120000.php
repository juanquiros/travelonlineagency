<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241212120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment method metadata to currencies and link transfer prices to configured currencies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE moneda ADD codigo_iso VARCHAR(3) NOT NULL DEFAULT \"ARS\", ADD metodo_pago VARCHAR(32) NOT NULL DEFAULT \"cash\"');
        $this->addSql('UPDATE moneda SET codigo_iso = UPPER(COALESCE(NULLIF(simbolo, \"\"), \"ARS\"))');
        $this->addSql("UPDATE moneda SET metodo_pago = 'mercadopago' WHERE UPPER(simbolo) = 'ARS'");
        $this->addSql("UPDATE moneda SET metodo_pago = 'paypal' WHERE UPPER(simbolo) = 'USD'");
        $this->addSql("UPDATE moneda SET metodo_pago = 'cash' WHERE metodo_pago NOT IN ('cash', 'mercadopago', 'paypal')");
        $this->addSql('ALTER TABLE moneda CHANGE habilitada habilitada TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE moneda MODIFY codigo_iso VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE moneda MODIFY metodo_pago VARCHAR(32) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F49DC47F8C0E4F0A ON moneda (codigo_iso)');

        $this->addSql('ALTER TABLE transfer_destination ADD moneda_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_destination ADD CONSTRAINT FK_EA9E48F38C0E4F0A FOREIGN KEY (moneda_id) REFERENCES moneda (id)');
        $this->addSql('CREATE INDEX IDX_EA9E48F38C0E4F0A ON transfer_destination (moneda_id)');

        $this->addSql('ALTER TABLE transfer_combo ADD moneda_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_combo ADD CONSTRAINT FK_81FF10508C0E4F0A FOREIGN KEY (moneda_id) REFERENCES moneda (id)');
        $this->addSql('CREATE INDEX IDX_81FF10508C0E4F0A ON transfer_combo (moneda_id)');

        $this->addSql("UPDATE transfer_destination SET moneda_id = (SELECT id FROM moneda WHERE codigo_iso = 'ARS' LIMIT 1) WHERE moneda_id IS NULL");
        $this->addSql("UPDATE transfer_combo SET moneda_id = (SELECT id FROM moneda WHERE codigo_iso = 'ARS' LIMIT 1) WHERE moneda_id IS NULL");

        $this->addSql('ALTER TABLE moneda ALTER codigo_iso DROP DEFAULT');
        $this->addSql('ALTER TABLE moneda ALTER metodo_pago DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_destination DROP FOREIGN KEY FK_EA9E48F38C0E4F0A');
        $this->addSql('DROP INDEX IDX_EA9E48F38C0E4F0A ON transfer_destination');
        $this->addSql('ALTER TABLE transfer_destination DROP moneda_id');

        $this->addSql('ALTER TABLE transfer_combo DROP FOREIGN KEY FK_81FF10508C0E4F0A');
        $this->addSql('DROP INDEX IDX_81FF10508C0E4F0A ON transfer_combo');
        $this->addSql('ALTER TABLE transfer_combo DROP moneda_id');

        $this->addSql('DROP INDEX UNIQ_F49DC47F8C0E4F0A ON moneda');
        $this->addSql('ALTER TABLE moneda DROP codigo_iso, DROP metodo_pago');
        $this->addSql('ALTER TABLE moneda CHANGE habilitada habilitada DOUBLE PRECISION NOT NULL');
    }
}
