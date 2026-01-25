<?php

declare(strict_types=1);

namespace Keystone\Plugin\Pages\Domain;

use Keystone\Domain\User\User;
use Keystone\Plugin\Pages\Infrastructure\Persistence\PageRepository;
use Keystone\Plugin\Pages\Infrastructure\Persistence\PageVersionRepository;
use Keystone\Plugin\Pages\Infrastructure\Persistence\PagePublicationRepository;
use Keystone\Plugin\Pages\Domain\Page;
use Keystone\Plugin\Pages\Domain\PagePolicy;
use Keystone\Domain\User\CurrentUser;
// use Keystone\Plugin\ContentBlocks\Service\BlockRenderer;
// use Keystone\Plugin\ContentBlocks\Service\BlockValidator;

final class PageService {

private const ALLOWED_TEMPLATES = [
    'default',
    'landing',
    'homepage',
    'full-width',
];


    public function __construct(
        private PageRepository $repository,
        private PageVersionRepository $version,
        private PagePolicy $policy,
        // private BlockValidator $blockValidator,
        private PagePublicationRepository $publications,
        private CurrentUser $currentUser,
        // private BlockRenderer $blocks
        ) {}

public function render(Page $page): string  {
        return $this->blocks->render($page->blocks());
    }


public function versions(int $pageId): array {
    return $this->version->allForPage($pageId);
}

public function revertToVersion(int $pageId, int $versionId): void
{
    $version = $this->version->find($versionId);

    if (!$version || $version['page_id'] !== $pageId) {
        throw new RuntimeException('Invalid version');
    }

    // Revert = nieuwe versie aanmaken
    $this->repository->create(
        $pageId,
        $version['title'],
        $version['content'],
        null
    );
}

public function executeScheduledPublications(): void {
    foreach ($this->publications->due() as $job) {

        $this->pages->publish(
            (int) $job['page_id'],
            (int) $job['version_id']
        );

        $this->publications->markExecuted(
            (int) $job['id']
        );
    }
}


public function delete(
       int $pageId

): void {

$this->repository->delete($pageId);

}

public function schedulePublish(
    int $pageId,
    int $versionId,
    \DateTimeInterface $publishAt
): void {

    $version = $this->version->find($versionId);

    if (!$version || $version['page_id'] !== $pageId) {
        throw new RuntimeException('Invalid version');
    }

    if ($publishAt <= new \DateTimeImmutable()) {
        throw new RuntimeException('Publish date must be in the future');
    }

    $this->publications->schedule(
        $pageId,
        $versionId,
        $publishAt,
        $this->currentUserId()
    );
}


public function detachMedia(int $pageId, int $mediaId): void {
    $page = $this->repository->findById($pageId);

    if (!$page) {
        throw new RuntimeException('Page not found');
    }

    $this->repository->detachMedia($pageId, $mediaId);
}

public function attachMedia(int $pageId, int $mediaId): void {
    $page = $this->repository->findById($pageId);

    if (!$page) {
        throw new RuntimeException('Page not found');
    }

    $this->repository->attachMedia($pageId, $mediaId);
}

public function media(int $pageId): array {
    return $this->repository->mediaForPage($pageId);
}

public function version(int $pageId, int $versionId): ?array
{
    $version = $this->version->find($versionId);

    if (!$version || $version['page_id'] !== $pageId) {
        return null;
    }

    return $version;
}

public function saveDraft(
    int $pageId,
    string $title,
    string $slug,
    string $content,
    string $template
): int {

    $content = $this->sanitizeHtml($content);

    $versionId = $this->versions->create(
        $pageId,
        $title,
        $content,
        $template,
        $this->currentUserId()
    );

    // snapshot voor editor
    $this->repository->update(
        $pageId,
        $title,
        $slug,
        $content,
        $template
    );

    return $versionId;
}

public function cleanupVersions(
    int $pageId,
    int $keepManual = 10,
    int $keepAutosave = 5
): int {

    $page = $this->repository->findById($pageId);

    if (!$page) {
        return 0;
    }

    $publishedId = (int) ($page->published_version_id() ?? 0);

    $versions = $this->version->allForPage($pageId);

    $manual = [];
    $autosave = [];

    foreach ($versions as $v) {

        if ((int) $v['id'] === $publishedId) {
            continue;
        }

        if (!empty($v['is_initial'])) {
            continue;
        }

        if ($v['is_autosave']) {
            $autosave[] = $v;
        } else {
            $manual[] = $v;
        }
    }

    // sort newest first
    usort($manual, fn($a, $b) =>
        strtotime($b['created_at']) <=> strtotime($a['created_at'])
    );
    usort($autosave, fn($a, $b) =>
        strtotime($b['created_at']) <=> strtotime($a['created_at'])
    );

    $toDelete = array_merge(
        array_slice($manual, $keepManual),
        array_slice($autosave, $keepAutosave)
    );

    foreach ($toDelete as $v) {
        $this->version->delete((int) $v['id']);
    }

    return count($toDelete);
}

public function autosave(int $pageId, array $data): void {
    $data['id'] = $pageId;

    // save page WITHOUT creating a new version
    $this->save($data, createVersion: false, autosave: true);

    // optional cleanup
    $this->cleanupVersions($pageId);
}



public function setHomepage(int $pageId): void {
        $page = $this->repository->findById($pageId);

        if (!$page) {
            throw new RuntimeException('Page not found');
        }

        if (!$this->policy->mayBeHomepage($page)) {
            throw new RuntimeException(
                'Only published pages can be set as homepage'
            );
        }

        $this->repository->unsetHomepage();
        $this->repository->setHomepage($pageId);
    }

public function publish(
    int $pageId,
    int $versionId
): void {

    $version = $this->version->find($versionId);

    if (!$version || $version['page_id'] !== $pageId) {
        throw new RuntimeException('Invalid version');
    }

    $this->repository->publish(
        $pageId,
        $versionId
    );
}


    public function unpublish(int $pageId): void {
        $page = $this->repository->findById($pageId);

        if (!$page) {
            throw new RuntimeException('Page not found');
        }

        // safety: homepage kan niet unpublished zijn
        if ($page->isHomepage()) {
            $this->repository->unsetHomepage();
        }

        $this->repository->updateStatus($pageId, 'draft');
    }

public function all(): array {
        return $this->repository->all();
    }

    public function findBySlug(string $slug): ?Page {
        return $this->repository->findBySlug($slug);
    }

   public function getHomepage(): ?Page
    {
        return $this->repository->findHomepage();
    }

   private function updatePageVersion(int $page_id, int $version_id) {
       return $this->repository->updatePageVersion($page_id, $version_id);
      }

   public function findById(int $id): ?Page {
        return $this->repository->findById($id);
    }

    public function create(array $data, User $user): Page {
        // hier later policies / logging / events
        return $this->repository->create($data, $user);
    }

public function save(
        array $data,
        bool $createVersion = true,
        bool $autosave = false
        ): ?int {

    $pageId = (int) ($data['id'] ?? 0);

    // ---------- content mode ----------
    $contentMode = $data['content_mode'] ?? 'richtext';

    $title    = trim($data['title'] ?? '');
    $slug     = trim($data['slug'] ?? '');
    $status   = $data['status'] ?? 'draft';
    $authorId = (int) ($data['authorId'] ?? 0);
    $template = $data['template'] ?? null;
    $contentMode = $contentMode;


    if ($title === '') {
        throw new RuntimeException('Title is required');
    }

    
    $slug = $slug === ''
        ? $this->slugify($title)
        : $this->slugify($slug);

    $this->assertValidTemplate($template);

    if (!in_array($contentMode, ['richtext', 'blocks'], true)) {
        throw new RuntimeException('Invalid content mode');
    }

    // ---------- content ----------
    $contentHtml = null;
    $blocks      = null;

    if ($contentMode === 'richtext') {
        $contentHtml = $this->sanitizeHtml(
            $data['content_html'] ?? ''
        );
    }


if ($contentMode === 'blocks') {
        $blocks = (array) $data['blocks'];
        }

if ($contentMode === 'blocks' && !$autosave) {
        $this->blockValidator->validate($data['blocks'] ?? []);
        }

    // ---------- persist page ----------
    if ($pageId != 0) {

         $this->repository->update(
            $pageId,
            $title,
            $slug,
            $status,
            $template,
            $contentMode,
            $contentHtml,
            $blocks
        );

    } else {

        $pageId = $this->repository->create(
            $title,
            $slug,
            $status,
            $authorId,
            $template,
            $contentMode,
            $contentHtml,
            $blocks
        );
    }

 if ($createVersion) {
  $versionId = $this->version->create(
        $pageId,
        $title,
        $contentMode === 'richtext'
            ? $contentHtml
            : json_encode($blocks, JSON_THROW_ON_ERROR),
        $this->currentUserId()
    );
        $this->updatePageVersion($pageId, $versionId);
        return $versionId;
    }

    return null;
}

private function assertValidTemplate(string $template): void {
    if (!in_array($template, self::ALLOWED_TEMPLATES, true)) {
        throw new RuntimeException('Invalid page template');
    }
}


private function currentUserId(): ?int
{
    return $this->currentUser->isAuthenticated()
        ? $this->currentUser->user()->id()
        : null;
}

private function sanitizeHtml(string $html): string
{
    return strip_tags(
        $html,
        '<p><br><strong><em><u><h2><h3><ul><ol><li><blockquote><img>'
    );
}


    /**
     * Maak een nette URL-slug
     */
private function slugify(string $value): string {
        $value = strtolower($value);
        $value = trim($value);

        // Accenten verwijderen (é → e, ü → u)
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        // Alles behalve letters, cijfers en spaties verwijderen
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);

        // Spaties en meerdere streepjes → enkel streepje
        $value = preg_replace('/[\s-]+/', '-', $value);

        return trim($value, '-');
        }
}

?>