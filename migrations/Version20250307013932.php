<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307013932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projects DROP description');
        $this->addSql('ALTER TABLE "user" DROP can_edit');
        $this->addSql('ALTER TABLE "user" DROP can_consult');
        $this->addSql('ALTER TABLE user_project_access ADD can_edit BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE user_project_access ADD can_consult BOOLEAN NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projects ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_project_access DROP can_edit');
        $this->addSql('ALTER TABLE user_project_access DROP can_consult');
        $this->addSql('ALTER TABLE "user" ADD can_edit BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD can_consult BOOLEAN NOT NULL');
    }
}
