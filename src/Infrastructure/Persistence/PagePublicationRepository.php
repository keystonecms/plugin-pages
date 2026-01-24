<?php

declare(strict_types=1);

namespace Keystone\Plugins\Pages\Infrastructure\Persistence;

use PDO;


final class PagePublicationRepository {
    public function __construct(
        private PDO $pdo
    ) {}

    public function schedule(
        int $pageId,
        int $versionId,
        \DateTimeInterface $publishAt,
        ?int $userId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO page_publications
             (page_id, version_id, publish_at, created_by)
             VALUES (:page, :version, :at, :user)'
        );

        $stmt->execute([
            'page'    => $pageId,
            'version' => $versionId,
            'at'      => $publishAt->format('Y-m-d H:i:s'),
            'user'    => $userId
        ]);
    }

    public function due(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM page_publications
             WHERE publish_at <= NOW()
             AND executed_at IS NULL'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markExecuted(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE page_publications
             SET executed_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
    }
}

?>