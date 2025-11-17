<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250122120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow deleting Mercado Pago credentials by making the payment FK nullable with ON DELETE SET NULL.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'mysql') {
            $this->addSql('ALTER TABLE mercado_pago_pago DROP FOREIGN KEY FK_F2410B447210C414');
            $this->addSql('ALTER TABLE mercado_pago_pago CHANGE credenciales_mercado_pago_id credenciales_mercado_pago_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE mercado_pago_pago ADD CONSTRAINT FK_F2410B447210C414 FOREIGN KEY (credenciales_mercado_pago_id) REFERENCES credenciales_mercado_pago (id) ON DELETE SET NULL');
            return;
        }

        if ($platform === 'sqlite') {
            $this->addSql('CREATE TABLE __temp__mercado_pago_pago (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, payment_id NUMERIC(20, 0) NOT NULL, preference_id VARCHAR(255) DEFAULT NULL, credenciales_mercado_pago_id INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, collector_id VARCHAR(255) DEFAULT NULL, payer CLOB DEFAULT NULL, transaction_amount DOUBLE PRECISION NOT NULL, transaction_amount_refunded DOUBLE PRECISION NOT NULL, payment_method_id VARCHAR(255) NOT NULL, payment_type_id VARCHAR(255) NOT NULL, card CLOB DEFAULT NULL, net_received_amount DOUBLE PRECISION NOT NULL, fee_details CLOB NOT NULL, application_fee DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, solicitud_reserva_id INTEGER DEFAULT NULL, transfer_request_id INTEGER DEFAULT NULL, CONSTRAINT FK_F2410B447210C414 FOREIGN KEY (credenciales_mercado_pago_id) REFERENCES credenciales_mercado_pago (id) ON DELETE SET NULL, CONSTRAINT FK_F2410B42BF8543F FOREIGN KEY (solicitud_reserva_id) REFERENCES solicitud_reserva (id), CONSTRAINT FK_F2410B419613132 FOREIGN KEY (transfer_request_id) REFERENCES transfer_request (id))');
            $this->addSql('INSERT INTO __temp__mercado_pago_pago (id, payment_id, preference_id, credenciales_mercado_pago_id, status, collector_id, payer, transaction_amount, transaction_amount_refunded, payment_method_id, payment_type_id, card, net_received_amount, fee_details, application_fee, created_at, updated_at, solicitud_reserva_id, transfer_request_id) SELECT id, payment_id, preference_id, credenciales_mercado_pago_id, status, collector_id, payer, transaction_amount, transaction_amount_refunded, payment_method_id, payment_type_id, card, net_received_amount, fee_details, application_fee, created_at, updated_at, solicitud_reserva_id, transfer_request_id FROM mercado_pago_pago');
            $this->addSql('DROP TABLE mercado_pago_pago');
            $this->addSql('ALTER TABLE __temp__mercado_pago_pago RENAME TO mercado_pago_pago');
            $this->addSql('CREATE INDEX IDX_A74A2EDC19613132 ON mercado_pago_pago (transfer_request_id)');
            return;
        }

        $this->abortIf(true, sprintf('Unsupported database platform: %s', $platform));
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'This migration cannot be safely reverted.');
    }
}
