<?php

declare(strict_types=1);

namespace Keystone\Plugin\Pages\Domain;

use Keystone\Core\Auth\PolicyInterface;
use Keystone\Domain\User\User;
use Keystone\Plugin\Pages\Domain\Page;

final class PagePolicy implements PolicyInterface
{

    public function mayView(Page $page): bool {
        return $page->getStatus() === 'published';
    }

    public function mayBeHomepage(Page $page): bool
    {
        return $page->status() === 'published';
    }

    public function allows(
        User $user,
        string $ability,
        mixed $resource = null
    ): bool {
        // MVP: admin mag alles
        if ($user->hasRole('admin')) {
            return true;
        }

        return match ($ability) {
            'view' => true,
            'edit', 'create', 'publish', 'delete' => false,
            default => false,
        };
    }
}

?>
