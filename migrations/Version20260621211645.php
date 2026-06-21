<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621211645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, excerpt LONGTEXT DEFAULT NULL, content LONGTEXT DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, published_at DATETIME DEFAULT NULL, is_published TINYINT NOT NULL, author_id INT DEFAULT NULL, INDEX IDX_23A0E66F675F31B (author_id), UNIQUE INDEX UNIQ_ARTICLE_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, subject VARCHAR(255) DEFAULT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, is_handled TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE email_verification_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C4995C67A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE loyalty_account (id INT AUTO_INCREMENT NOT NULL, points_balance INT NOT NULL, tier VARCHAR(50) DEFAULT NULL, lifetime_points INT NOT NULL, customer_id INT NOT NULL, UNIQUE INDEX UNIQ_11F7BE179395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE loyalty_transaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, points INT NOT NULL, created_at DATETIME NOT NULL, account_id INT NOT NULL, order_id INT DEFAULT NULL, INDEX IDX_4CE4AEC19B6B5FBA (account_id), INDEX IDX_4CE4AEC18D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_subscription (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, is_confirmed TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_NEWSLETTER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, is_read TINYINT NOT NULL, data JSON DEFAULT NULL, created_at DATETIME NOT NULL, customer_id INT NOT NULL, INDEX IDX_BF5476CA9395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, is_published TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_PAGE_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_6B7BA4B6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(50) NOT NULL, last4 VARCHAR(4) NOT NULL, exp_month INT NOT NULL, exp_year INT NOT NULL, provider_token VARCHAR(255) NOT NULL, is_default TINYINT NOT NULL, customer_id INT NOT NULL, INDEX IDX_7B61A1F69395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_variant (id INT AUTO_INCREMENT NOT NULL, size VARCHAR(50) DEFAULT NULL, color VARCHAR(100) DEFAULT NULL, color_hex VARCHAR(7) DEFAULT NULL, sku VARCHAR(100) DEFAULT NULL, stock INT NOT NULL, price_modifier DOUBLE PRECISION DEFAULT NULL, is_active TINYINT NOT NULL, product_id INT NOT NULL, INDEX IDX_209AA41D4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE return_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, return_request_id INT NOT NULL, order_item_id INT NOT NULL, INDEX IDX_7EED95F789EA1297 (return_request_id), INDEX IDX_7EED95F7E415FB15 (order_item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE return_request (id INT AUTO_INCREMENT NOT NULL, status INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, type VARCHAR(20) NOT NULL, refund_amount DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, order_id INT NOT NULL, customer_id INT NOT NULL, INDEX IDX_2DBF9D408D9F6D38 (order_id), INDEX IDX_2DBF9D409395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review_vote (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, review_id INT NOT NULL, customer_id INT NOT NULL, INDEX IDX_B8A4C87C3E2E969B (review_id), INDEX IDX_B8A4C87C9395C3F3 (customer_id), UNIQUE INDEX UNIQ_REVIEW_VOTE (review_id, customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shipping_method (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(50) NOT NULL, price DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, estimated_days INT DEFAULT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE email_verification_token ADD CONSTRAINT FK_C4995C67A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE loyalty_account ADD CONSTRAINT FK_11F7BE179395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE loyalty_transaction ADD CONSTRAINT FK_4CE4AEC19B6B5FBA FOREIGN KEY (account_id) REFERENCES loyalty_account (id)');
        $this->addSql('ALTER TABLE loyalty_transaction ADD CONSTRAINT FK_4CE4AEC18D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F69395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE return_item ADD CONSTRAINT FK_7EED95F789EA1297 FOREIGN KEY (return_request_id) REFERENCES return_request (id)');
        $this->addSql('ALTER TABLE return_item ADD CONSTRAINT FK_7EED95F7E415FB15 FOREIGN KEY (order_item_id) REFERENCES order_item (id)');
        $this->addSql('ALTER TABLE return_request ADD CONSTRAINT FK_2DBF9D408D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE return_request ADD CONSTRAINT FK_2DBF9D409395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE review_vote ADD CONSTRAINT FK_B8A4C87C3E2E969B FOREIGN KEY (review_id) REFERENCES review (id)');
        $this->addSql('ALTER TABLE review_vote ADD CONSTRAINT FK_B8A4C87C9395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart_item ADD product_variant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id)');
        $this->addSql('CREATE INDEX IDX_F0FE2527A80EF684 ON cart_item (product_variant_id)');
        $this->addSql('ALTER TABLE category ADD slug VARCHAR(255) DEFAULT NULL, ADD position INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_deleted TINYINT DEFAULT NULL, ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('ALTER TABLE `order` ADD tax_total DOUBLE PRECISION DEFAULT NULL, ADD tracking_number VARCHAR(255) DEFAULT NULL, ADD carrier VARCHAR(255) DEFAULT NULL, ADD gift_wrap TINYINT NOT NULL, ADD gift_message VARCHAR(500) DEFAULT NULL, ADD refunded_at DATETIME DEFAULT NULL, ADD shipping_method_id INT DEFAULT NULL, ADD shipping_address_id INT DEFAULT NULL, ADD billing_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993985F7D6850 FOREIGN KEY (shipping_method_id) REFERENCES shipping_method (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939879D0C0E4 FOREIGN KEY (billing_address_id) REFERENCES address (id)');
        $this->addSql('CREATE INDEX IDX_F52993985F7D6850 ON `order` (shipping_method_id)');
        $this->addSql('CREATE INDEX IDX_F52993984D4CFF2B ON `order` (shipping_address_id)');
        $this->addSql('CREATE INDEX IDX_F529939879D0C0E4 ON `order` (billing_address_id)');
        $this->addSql('ALTER TABLE order_item ADD product_image VARCHAR(255) DEFAULT NULL, ADD size VARCHAR(50) DEFAULT NULL, ADD color VARCHAR(100) DEFAULT NULL, ADD product_id INT DEFAULT NULL, ADD product_variant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id)');
        $this->addSql('CREATE INDEX IDX_52EA1F094584665A ON order_item (product_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F09A80EF684 ON order_item (product_variant_id)');
        $this->addSql('ALTER TABLE product ADD slug VARCHAR(255) DEFAULT NULL, ADD subtitle VARCHAR(255) DEFAULT NULL, ADD old_price VARCHAR(255) DEFAULT NULL, ADD badge VARCHAR(50) DEFAULT NULL, ADD is_featured TINYINT NOT NULL, ADD is_new TINYINT NOT NULL, ADD stock INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_media ADD sort_order INT DEFAULT NULL, ADD is_primary TINYINT NOT NULL, ADD type VARCHAR(20) DEFAULT NULL, ADD alt VARCHAR(255) DEFAULT NULL, ADD color VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD helpful_count INT NOT NULL, ADD is_verified_purchase TINYINT NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD order_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6E415FB15 FOREIGN KEY (order_item_id) REFERENCES order_item (id)');
        $this->addSql('CREATE INDEX IDX_794381C6E415FB15 ON review (order_item_id)');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT NOT NULL, ADD email_verified_at DATETIME DEFAULT NULL, ADD default_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649BD94FB16 FOREIGN KEY (default_address_id) REFERENCES address (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649BD94FB16 ON user (default_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66F675F31B');
        $this->addSql('ALTER TABLE email_verification_token DROP FOREIGN KEY FK_C4995C67A76ED395');
        $this->addSql('ALTER TABLE loyalty_account DROP FOREIGN KEY FK_11F7BE179395C3F3');
        $this->addSql('ALTER TABLE loyalty_transaction DROP FOREIGN KEY FK_4CE4AEC19B6B5FBA');
        $this->addSql('ALTER TABLE loyalty_transaction DROP FOREIGN KEY FK_4CE4AEC18D9F6D38');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA9395C3F3');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE payment_method DROP FOREIGN KEY FK_7B61A1F69395C3F3');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D4584665A');
        $this->addSql('ALTER TABLE return_item DROP FOREIGN KEY FK_7EED95F789EA1297');
        $this->addSql('ALTER TABLE return_item DROP FOREIGN KEY FK_7EED95F7E415FB15');
        $this->addSql('ALTER TABLE return_request DROP FOREIGN KEY FK_2DBF9D408D9F6D38');
        $this->addSql('ALTER TABLE return_request DROP FOREIGN KEY FK_2DBF9D409395C3F3');
        $this->addSql('ALTER TABLE review_vote DROP FOREIGN KEY FK_B8A4C87C3E2E969B');
        $this->addSql('ALTER TABLE review_vote DROP FOREIGN KEY FK_B8A4C87C9395C3F3');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE email_verification_token');
        $this->addSql('DROP TABLE loyalty_account');
        $this->addSql('DROP TABLE loyalty_transaction');
        $this->addSql('DROP TABLE newsletter_subscription');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE product_variant');
        $this->addSql('DROP TABLE return_item');
        $this->addSql('DROP TABLE return_request');
        $this->addSql('DROP TABLE review_vote');
        $this->addSql('DROP TABLE shipping_method');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527A80EF684');
        $this->addSql('DROP INDEX IDX_F0FE2527A80EF684 ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP product_variant_id');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('DROP INDEX IDX_64C19C1727ACA70 ON category');
        $this->addSql('ALTER TABLE category DROP slug, DROP position, DROP created_at, DROP updated_at, DROP is_deleted, DROP parent_id');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993985F7D6850');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984D4CFF2B');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939879D0C0E4');
        $this->addSql('DROP INDEX IDX_F52993985F7D6850 ON `order`');
        $this->addSql('DROP INDEX IDX_F52993984D4CFF2B ON `order`');
        $this->addSql('DROP INDEX IDX_F529939879D0C0E4 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP tax_total, DROP tracking_number, DROP carrier, DROP gift_wrap, DROP gift_message, DROP refunded_at, DROP shipping_method_id, DROP shipping_address_id, DROP billing_address_id');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09A80EF684');
        $this->addSql('DROP INDEX IDX_52EA1F094584665A ON order_item');
        $this->addSql('DROP INDEX IDX_52EA1F09A80EF684 ON order_item');
        $this->addSql('ALTER TABLE order_item DROP product_image, DROP size, DROP color, DROP product_id, DROP product_variant_id');
        $this->addSql('ALTER TABLE product DROP slug, DROP subtitle, DROP old_price, DROP badge, DROP is_featured, DROP is_new, DROP stock');
        $this->addSql('ALTER TABLE product_media DROP sort_order, DROP is_primary, DROP type, DROP alt, DROP color');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E415FB15');
        $this->addSql('DROP INDEX IDX_794381C6E415FB15 ON review');
        $this->addSql('ALTER TABLE review DROP helpful_count, DROP is_verified_purchase, DROP updated_at, DROP order_item_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649BD94FB16');
        $this->addSql('DROP INDEX IDX_8D93D649BD94FB16 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP email_verified_at, DROP default_address_id');
    }
}
