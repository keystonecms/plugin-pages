<?php

namespace Keystone\Plugin\Pages;

use Keystone\Core\Plugin\PluginInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use function DI\autowire;
use Slim\Views\Twig;

use Keystone\Plugin\Pages\Dashboard\PagesCountWidget;
use Keystone\Plugin\Pages\Domain\PageRepositoryInterface;
use Keystone\Plugin\Pages\Domain\PageService;
use Keystone\Plugin\Pages\Infrastructure\Persistence\PageRepository;
use Keystone\Plugin\Pages\Domain\PagePolicy;
use Keystone\Domain\Menu\Service\LinkResolver;
use Keystone\Admin\Menu\AdminMenuRegistry;
use Keystone\Core\Dashboard\DashboardWidgetRegistry;

final class Plugin implements PluginInterface {

    public function getName(): string {
        return 'Pages';
    }

     public function getVersion(): string {
        return 'v1.0.0';
    }

    public function getDescription(): string
    {
        return 'Cores pages app description';
    }

    public function getLoadOrder(): int {
    return 999; // catch-all
    }



public function register(ContainerInterface $container): void {
    $container->set(
        PageRepositoryInterface::class,
        autowire(PageRepository::class)
    );

    $container->set(
        PageService::class,
        autowire()
    );

$container->set(PagesCountWidget::class, autowire());

    $container
        ->get(DashboardWidgetRegistry::class)
        ->add(
            $container->get(PagesCountWidget::class)
        );

}


    public function boot(App $app, ContainerInterface $container): void {

        $twig = $container->get(Twig::class);

        $menu = $container->get(AdminMenuRegistry::class);


        $menu->add([
            'id'    => 'pages',
            'label' => 'Pages',
            'icon'  => 'bi bi-journal-text',
            'order' => 30,
            'match' => 'admin.pages.',
            'route' => 'admin.pages.index',
            'children' => [
                [
                    'label' => 'Overview',
                    'route' => 'admin.pages.index',
                    'icon'  => 'bi bi-file-text',
                ],
                [
                    'label' => 'Create',
                    'route' => 'admin.pages.create',
                    'icon'  => 'bi bi-chat-left-text',
                ],
            ],
        ]);


        $twig->getLoader()->addPath(
            __DIR__ . '/views',
            'pages'
        );

        require __DIR__ . '/routes/admin.php';
        require __DIR__ . '/routes/public.php';


    // Menu link resolver via PageService
    $linkResolver = $container->get(LinkResolver::class);
    $pages = $container->get(PageService::class);

    $linkResolver->register('page', function ($item) use ($pages) {
        $page = $pages->findById((int) $item->linkTarget());

        return $page
            ? '/' . ltrim($page->slug(), '/')
            : '#';
        });
    }

};

?>