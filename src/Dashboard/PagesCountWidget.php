<?php

namespace Keystone\Plugin\Pages\Dashboard;

use Keystone\Core\Dashboard\DashboardWidgetInterface;
use Keystone\Plugin\Pages\Infrastructure\Persistence\PageRepository;

final class PagesCountWidget implements DashboardWidgetInterface {
    
    public function __construct(
        private PageRepository $pages
    ) {}

    public function getId(): string
    {
        return 'pages.count';
    }

    public function getTitle(): string
    {
        return 'Pages';
    }

    public function render(): string
    {
        $count = $this->pages->countAll();

        return "<strong>{$count}</strong> pages";
    }
}


?>