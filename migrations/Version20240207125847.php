<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée les tables de base pour Clocking
 */
final class Version20240207125847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create clocking, project and user tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE clocking (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, duration INT NOT NULL, clocking_project_id INT NOT NULL, clocking_user_id INT NOT NULL, INDEX IDX_D3E9DCCD4431A71B (clocking_project_id), INDEX IDX_D3E9DCCDA1F846FC (clocking_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL, date_end DATE DEFAULT NULL, date_start DATE NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, matricule VARCHAR(255) NOT NULL, email VARCHAR(180) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64912B2DC9C (matricule), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCD4431A71B FOREIGN KEY (clocking_project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE clocking ADD CONSTRAINT FK_D3E9DCCDA1F846FC FOREIGN KEY (clocking_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCD4431A71B');
        $this->addSql('ALTER TABLE clocking DROP FOREIGN KEY FK_D3E9DCCDA1F846FC');
        $this->addSql('DROP TABLE clocking');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
