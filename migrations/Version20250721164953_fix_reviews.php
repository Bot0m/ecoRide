<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour corriger les avis non validés
 */
final class Version20250721164953_fix_reviews extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction des avis non validés - permettre NULL et corriger les données';
    }

    public function up(Schema $schema): void
    {
        // D'abord permettre les valeurs NULL dans la colonne is_validated
        $this->addSql("ALTER TABLE review MODIFY is_validated TINYINT(1) NULL");
        
        // Mettre tous les avis avec isValidated = false à NULL (non traités)
        $this->addSql("UPDATE review SET is_validated = NULL WHERE is_validated = 0");
    }

    public function down(Schema $schema): void
    {
        // Remettre les avis non traités à false
        $this->addSql("UPDATE review SET is_validated = 0 WHERE is_validated IS NULL");
        
        // Remettre la contrainte NOT NULL
        $this->addSql("ALTER TABLE review MODIFY is_validated TINYINT(1) NOT NULL");
    }
} 