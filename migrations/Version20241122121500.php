<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Mercado Pago split support for partners and platform balances.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credenciales_mercado_pago ADD nickname VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking_partner ADD mercado_pago_cuenta_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking_partner ADD CONSTRAINT FK_6E39C9F86E0D339B FOREIGN KEY (mercado_pago_cuenta_id) REFERENCES credenciales_mercado_pago (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6E39C9F86E0D339B ON booking_partner (mercado_pago_cuenta_id)');
        $this->addSql('ALTER TABLE mercado_pago_pago ADD application_fee DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_partner DROP FOREIGN KEY FK_6E39C9F86E0D339B');
        $this->addSql('DROP INDEX UNIQ_6E39C9F86E0D339B ON booking_partner');
        $this->addSql('ALTER TABLE booking_partner DROP mercado_pago_cuenta_id');
        $this->addSql('ALTER TABLE credenciales_mercado_pago DROP nickname, DROP email');
        $this->addSql('ALTER TABLE mercado_pago_pago DROP application_fee');
    }
}
