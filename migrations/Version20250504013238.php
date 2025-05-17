<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20250504013238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
    $this->addSql('ALTER TABLE occupation_record ADD week_end DATE DEFAULT CURRENT_DATE NOT NULL');
    
    $this->addSql('ALTER TABLE occupation_record ALTER COLUMN week_end DROP DEFAULT');
    
    
    $this->addSql('ALTER TABLE occupation_record RENAME COLUMN date TO week_start');
    $this->addSql('ALTER TABLE occupation_record ALTER week_start TYPE DATE');
    $this->addSql('ALTER TABLE resource DROP occupation_rate');
    $this->addSql('ALTER TABLE resource DROP availability_rate');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE occupation_record ADD date DATE NOT NULL');
        $this->addSql('ALTER TABLE occupation_record DROP week_start');
        $this->addSql('ALTER TABLE occupation_record DROP week_end');
        $this->addSql('ALTER TABLE resource ADD occupation_rate DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE resource ADD availability_rate DOUBLE PRECISION NOT NULL');
    }
}
