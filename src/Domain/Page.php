<?php

declare(strict_types=1);

/*
 * Keystone CMS
 *
 * @package   Keystone CMS
 * @license   MIT
 * @link      https://keystone-cms.com
 */

namespace Keystone\Plugin\Pages\Domain;

final class Page
{
    public function __construct(
        private int $id,
        private string $title,
        private string $slug,
        private string $content_mode,
        private ?string $content_html,
        private string $status,
        private int $authorId,
        private string $template,
        private bool $isHomepage,
        private int $published_version_id,
        private ?string $next_publication,
        private ?array $blocks = null

    ) {}

    public function id(): int { return $this->id; }
    public function title(): string { return $this->title; }
    public function slug(): string { return $this->slug; }
    public function content_mode(): string { return $this->content_mode; }
    public function content_html(): ?string { return $this->content_html; }
    public function status(): string { return $this->status; }
    public function authorId(): int { return $this->authorId; }
    public function template(): string { return $this->template; }
    public function published_version_id(): int { return $this->published_version_id; }
    public function next_publication(): ?string { return $this->next_publication; }

public function editUrl(): string {
    return match ($this->content_mode()) {
        'blocks'   => '/admin/pages/' . $this->id() . '/edit-blocks',
        'richtext' => '/admin/pages/' . $this->id() . '/edit-richtext',
        default    => '/admin/pages/' . $this->id() . '/edit-richtext',
    };
}

    public function isPublished(): bool {
        return $this->status === 'published';
    }
    public function isHomepage(): bool {
        return $this->isHomepage;
    }

    public function blocks(): array {
    return $this->blocks ?? [];
    }

    public function setBlocks(array $blocks): void {
    $this->blocks = $blocks;
    }
}

    ?>