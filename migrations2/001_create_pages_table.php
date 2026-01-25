<?php
# test
use Keystone\Core\Migration\MigrationInterface;
use PDO;

return new class implements MigrationInterface {

    public function getPlugin(): string
    {
        return 'Pages';
    }

    public function getVersion(): string
    {
        return '001_create_pages_table';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content TEXT NOT NULL,
                published TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL
            )'
        );
    }
};


?>