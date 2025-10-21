<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241123093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cash payments table and platform payment toggles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cash_payment (id INT AUTO_INCREMENT NOT NULL, solicitud_reserva_id INT DEFAULT NULL, transfer_request_id INT DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(32) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'DC2Type:datetime_immutable\', updated_at DATETIME NOT NULL COMMENT \'DC2Type:datetime_immutable\', reference VARCHAR(255) DEFAULT NULL, INDEX IDX_861E808579BF5CA6 (solicitud_reserva_id), INDEX IDX_861E8085A94DB05C (transfer_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cash_payment ADD CONSTRAINT FK_861E808579BF5CA6 FOREIGN KEY (solicitud_reserva_id) REFERENCES solicitud_reserva (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_payment ADD CONSTRAINT FK_861E8085A94DB05C FOREIGN KEY (transfer_request_id) REFERENCES transfer_request (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE plataforma ADD enable_mercado_pago_payments TINYINT(1) DEFAULT 1 NOT NULL, ADD enable_pay_pal_payments TINYINT(1) DEFAULT 1 NOT NULL, ADD enable_cash_payments TINYINT(1) DEFAULT 0 NOT NULL, ADD cash_payment_instructions VARCHAR(1024) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_payment DROP FOREIGN KEY FK_861E808579BF5CA6');
        $this->addSql('ALTER TABLE cash_payment DROP FOREIGN KEY FK_861E8085A94DB05C');
        $this->addSql('DROP TABLE cash_payment');
        $this->addSql('ALTER TABLE plataforma DROP enable_mercado_pago_payments, DROP enable_pay_pal_payments, DROP enable_cash_payments, DROP cash_payment_instructions');
    }
}
