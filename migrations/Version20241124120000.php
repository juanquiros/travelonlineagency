<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241124120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Driver balances, withdrawals, commission fields and cash payment tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE driver_balance_entry (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, created_by_id INT DEFAULT NULL, cash_payment_id INT DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, direction VARCHAR(16) NOT NULL, type VARCHAR(40) NOT NULL, description LONGTEXT DEFAULT NULL, reference VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E9EB550B5E3C61F9 (driver_id), INDEX IDX_E9EB550BB03A8386 (created_by_id), UNIQUE INDEX UNIQ_E9EB550BFB54A090 (cash_payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE driver_withdrawal_request (id INT AUTO_INCREMENT NOT NULL, driver_id INT NOT NULL, requested_by_id INT DEFAULT NULL, processed_by_id INT DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(32) NOT NULL, notes LONGTEXT DEFAULT NULL, admin_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL, INDEX IDX_27A07E545E3C61F9 (driver_id), INDEX IDX_27A07E54E7A1254A (requested_by_id), INDEX IDX_27A07E548C0E4F0A (processed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE driver_profile ADD commission_percentage NUMERIC(5, 2) NOT NULL DEFAULT 0, ADD cbu VARCHAR(32) DEFAULT NULL, ADD cvu VARCHAR(32) DEFAULT NULL, ADD bank_alias VARCHAR(50) DEFAULT NULL');
        $this->addSql('UPDATE driver_profile SET commission_percentage = 0 WHERE commission_percentage IS NULL');
        $this->addSql('ALTER TABLE transfer_assignment ADD earning_entry_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cash_payment ADD driver_reported_at DATETIME DEFAULT NULL, ADD admin_confirmed_at DATETIME DEFAULT NULL, ADD driver_reported_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE driver_balance_entry ADD CONSTRAINT FK_E9EB550B5E3C61F9 FOREIGN KEY (driver_id) REFERENCES driver_profile (id)');
        $this->addSql('ALTER TABLE driver_balance_entry ADD CONSTRAINT FK_E9EB550BB03A8386 FOREIGN KEY (created_by_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE driver_balance_entry ADD CONSTRAINT FK_E9EB550BFB54A090 FOREIGN KEY (cash_payment_id) REFERENCES cash_payment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE driver_withdrawal_request ADD CONSTRAINT FK_27A07E545E3C61F9 FOREIGN KEY (driver_id) REFERENCES driver_profile (id)');
        $this->addSql('ALTER TABLE driver_withdrawal_request ADD CONSTRAINT FK_27A07E54E7A1254A FOREIGN KEY (requested_by_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE driver_withdrawal_request ADD CONSTRAINT FK_27A07E548C0E4F0A FOREIGN KEY (processed_by_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE transfer_assignment ADD CONSTRAINT FK_AB8CAEF4862B4D22 FOREIGN KEY (earning_entry_id) REFERENCES driver_balance_entry (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE cash_payment ADD CONSTRAINT FK_9EBDE866296F5D6 FOREIGN KEY (driver_reported_by_id) REFERENCES driver_profile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB8CAEF4862B4D22 ON transfer_assignment (earning_entry_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transfer_assignment DROP FOREIGN KEY FK_AB8CAEF4862B4D22');
        $this->addSql('DROP INDEX UNIQ_AB8CAEF4862B4D22 ON transfer_assignment');
        $this->addSql('ALTER TABLE driver_balance_entry DROP FOREIGN KEY FK_E9EB550BFB54A090');
        $this->addSql('ALTER TABLE driver_balance_entry DROP FOREIGN KEY FK_E9EB550BB03A8386');
        $this->addSql('ALTER TABLE driver_balance_entry DROP FOREIGN KEY FK_E9EB550B5E3C61F9');
        $this->addSql('ALTER TABLE driver_withdrawal_request DROP FOREIGN KEY FK_27A07E548C0E4F0A');
        $this->addSql('ALTER TABLE driver_withdrawal_request DROP FOREIGN KEY FK_27A07E54E7A1254A');
        $this->addSql('ALTER TABLE driver_withdrawal_request DROP FOREIGN KEY FK_27A07E545E3C61F9');
        $this->addSql('ALTER TABLE cash_payment DROP FOREIGN KEY FK_9EBDE866296F5D6');
        $this->addSql('ALTER TABLE cash_payment DROP driver_reported_at, DROP admin_confirmed_at, DROP driver_reported_by_id');
        $this->addSql('ALTER TABLE transfer_assignment DROP earning_entry_id');
        $this->addSql('ALTER TABLE driver_profile DROP commission_percentage, DROP cbu, DROP cvu, DROP bank_alias');
        $this->addSql('DROP TABLE driver_balance_entry');
        $this->addSql('DROP TABLE driver_withdrawal_request');
    }
}
