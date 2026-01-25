<?php
namespace Keystone\Plugin\Pages\Infrastructure\Persistence;

use PDO;

final class PageVersionRepository {
    public function __construct(
        private PDO $pdo
    ) {}

public function findCleanupCandidates(
    int $pageId,
    int $keepManual,
    int $keepAutosave,
    int $publishedVersionId
): array {

    $stmt = $this->pdo->prepare(
        '
        SELECT id
        FROM page_versions
        WHERE page_id = :page
          AND id != :published
          AND (
            (is_autosave = 1)
            OR
            (is_autosave = 0)
          )
        ORDER BY
            is_autosave ASC,
            created_at DESC
        '
    );

    $stmt->execute([
        'page'      => $pageId,
        'published' => $publishedVersionId ?? 0,
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

public function delete(int $id): void
{
    $stmt = $this->pdo->prepare(
        'DELETE FROM page_versions WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);
}


public function allForPage(int $pageId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM page_versions
             WHERE page_id = :id
             ORDER BY created_at DESC'
        );
        $stmt->execute(['id' => $pageId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM page_versions WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

public function create(
    int $pageId,
    string $title,
    string $content,
    ?int $userId,
    bool $autosave = false
): int {
    $stmt = $this->pdo->prepare(
        'INSERT INTO page_versions
         (page_id, title, content, created_by, is_autosave)
         VALUES (:page, :title, :content, :user, :autosave)'
    );

    $stmt->execute([
        'page'     => $pageId,
        'title'    => $title,
        'content'  => $content,
        'user'     => $userId,
        'autosave' => $autosave ? 1 : 0,
    ]);

    return (int) $this->pdo->lastInsertId();
}

}

?>