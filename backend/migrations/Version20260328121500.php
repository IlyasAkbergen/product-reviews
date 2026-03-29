<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: users, products, reviews (MySQL 8 / docker-compose)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id CHAR(36) NOT NULL,
                email VARCHAR(180) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE products (
                id CHAR(36) NOT NULL,
                external_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                price NUMERIC(10, 2) NOT NULL,
                category VARCHAR(100) NOT NULL,
                thumbnail VARCHAR(512) DEFAULT NULL,
                stock INT NOT NULL,
                brand VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_B3BA5A5A9F75D7B0 (external_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE reviews (
                id CHAR(36) NOT NULL,
                product_id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                rating SMALLINT NOT NULL,
                body LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uq_product_user (product_id, user_id),
                INDEX idx_reviews_product_id (product_id),
                INDEX idx_reviews_user_id (user_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY fk_reviews_product');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY fk_reviews_user');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE users');
    }
}
