<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216115415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart ADD session_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA388B7844A19ED ON cart (session_token)');
        $this->addSql('DROP INDEX UNIQ_F0FE2527844A19ED ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP session_token');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_BA388B7844A19ED ON cart');
        $this->addSql('ALTER TABLE cart DROP session_token');
        $this->addSql('ALTER TABLE cart_item ADD session_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F0FE2527844A19ED ON cart_item (session_token)');
    }
}
