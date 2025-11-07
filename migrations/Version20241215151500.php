<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241215151500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store supported payment methods per currency and track multi-currency totals for transfers and prices.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE moneda ADD metodos_pago JSON DEFAULT NULL');
        $this->addSql("UPDATE moneda SET metodos_pago = JSON_ARRAY(metodo_pago) WHERE metodo_pago IS NOT NULL AND metodo_pago <> ''");
        $this->addSql("UPDATE moneda SET metodos_pago = JSON_ARRAY('cash') WHERE metodos_pago IS NULL OR JSON_LENGTH(metodos_pago) = 0");
        $this->addSql('ALTER TABLE moneda MODIFY metodos_pago JSON NOT NULL');
        $this->addSql('ALTER TABLE moneda ALTER metodos_pago DROP DEFAULT');
        $this->addSql('ALTER TABLE moneda DROP metodo_pago');

        $this->addSql('ALTER TABLE precio ADD transfer_destination_id INT DEFAULT NULL, ADD transfer_combo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE precio ADD CONSTRAINT FK_PRECIO_TRANSFER_DEST FOREIGN KEY (transfer_destination_id) REFERENCES transfer_destination (id)');
        $this->addSql('ALTER TABLE precio ADD CONSTRAINT FK_PRECIO_TRANSFER_COMBO FOREIGN KEY (transfer_combo_id) REFERENCES transfer_combo (id)');
        $this->addSql('CREATE INDEX IDX_PRECIO_TRANSFER_DEST ON precio (transfer_destination_id)');
        $this->addSql('CREATE INDEX IDX_PRECIO_TRANSFER_COMBO ON precio (transfer_combo_id)');

        $this->addSql('ALTER TABLE transfer_request ADD totales_por_moneda JSON DEFAULT NULL');
        $this->addSql("UPDATE transfer_request SET totales_por_moneda = JSON_OBJECT(UPPER(moneda), CAST(precio_total AS DECIMAL(10, 2))) WHERE moneda IS NOT NULL AND moneda <> ''");
        $this->addSql("UPDATE transfer_request SET totales_por_moneda = JSON_OBJECT('ARS', CAST(precio_total AS DECIMAL(10, 2))) WHERE totales_por_moneda IS NULL");
        $this->addSql('ALTER TABLE transfer_request MODIFY totales_por_moneda JSON NOT NULL');
        $this->addSql('ALTER TABLE transfer_request ALTER totales_por_moneda DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_request DROP totales_por_moneda');

        $this->addSql('ALTER TABLE precio DROP FOREIGN KEY FK_PRECIO_TRANSFER_DEST');
        $this->addSql('ALTER TABLE precio DROP FOREIGN KEY FK_PRECIO_TRANSFER_COMBO');
        $this->addSql('DROP INDEX IDX_PRECIO_TRANSFER_DEST ON precio');
        $this->addSql('DROP INDEX IDX_PRECIO_TRANSFER_COMBO ON precio');
        $this->addSql('ALTER TABLE precio DROP transfer_destination_id, DROP transfer_combo_id');

        $this->addSql("ALTER TABLE moneda ADD metodo_pago VARCHAR(32) NOT NULL DEFAULT 'cash'");
        $this->addSql("UPDATE moneda SET metodo_pago = JSON_UNQUOTE(JSON_EXTRACT(metodos_pago, '$[0]')) WHERE JSON_LENGTH(metodos_pago) > 0");
        $this->addSql("UPDATE moneda SET metodo_pago = 'cash' WHERE metodo_pago IS NULL OR metodo_pago = ''");
        $this->addSql('ALTER TABLE moneda DROP metodos_pago');
        $this->addSql('ALTER TABLE moneda MODIFY metodo_pago VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE moneda ALTER metodo_pago DROP DEFAULT');
    }
}
