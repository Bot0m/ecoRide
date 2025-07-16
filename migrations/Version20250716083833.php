<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716083833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Ajouter la colonne avec une valeur par défaut temporaire
        $this->addSql('ALTER TABLE ride ADD created_at DATETIME NULL');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME NULL');
        
        // Mettre des dates réalistes pour les enregistrements existants
        $this->addSql("UPDATE user SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) WHERE created_at IS NULL");
        $this->addSql("UPDATE ride SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 14) DAY) WHERE created_at IS NULL");
        
        // Rendre les colonnes NOT NULL maintenant qu'elles ont des valeurs
        $this->addSql('ALTER TABLE ride MODIFY created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP created_at
        SQL);
    }
}
