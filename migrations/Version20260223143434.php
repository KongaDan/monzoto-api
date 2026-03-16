<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223143434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE currency currency VARCHAR(10) NOT NULL, CHANGE currency_shipping currency_shipping VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE currency currency VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE promo CHANGE currency currency VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE currency currency VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE gender gender VARCHAR(10) DEFAULT NULL, CHANGE language language VARCHAR(50) DEFAULT NULL, CHANGE currency currency VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE currency_shipping currency_shipping VARCHAR(255) DEFAULT NULL, CHANGE currency currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE currency currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE promo CHANGE currency currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE currency currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE gender gender VARCHAR(255) DEFAULT NULL, CHANGE language language VARCHAR(255) DEFAULT NULL, CHANGE currency currency VARCHAR(255) DEFAULT NULL');
    }
}
