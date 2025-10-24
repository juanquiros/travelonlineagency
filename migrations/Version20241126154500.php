<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241126154500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega enlaces sociales y sitio web a los destinos de traslado.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('transfer_destination');
        $table->addColumn('instagram', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('x', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('facebook', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('whatsapp', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('sitio_web', 'string', ['length' => 255, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('transfer_destination');
        $table->dropColumn('instagram');
        $table->dropColumn('x');
        $table->dropColumn('facebook');
        $table->dropColumn('whatsapp');
        $table->dropColumn('sitio_web');
    }
}
