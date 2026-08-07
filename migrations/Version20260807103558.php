<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807103558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE "user" ADD verification_code VARCHAR(6) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD verification_code_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE "user" SET is_verified = true');
        $this->addSql('UPDATE "user" SET email = LOWER(email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP is_verified');
        $this->addSql('ALTER TABLE "user" DROP verification_code');
        $this->addSql('ALTER TABLE "user" DROP verification_code_expires_at');
    }
}
