<?php

declare(strict_types=1);

namespace Keystone\Plugin\Pages\Controller\Public;

use Keystone\Plugin\Pages\Domain\PageService;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;


use Keystone\Plugin\Seo\Domain\SeoSubject;
use Keystone\Plugin\Seo\Domain\SeoService;
use Keystone\Domain\Menu\Repository\MenuRepositoryInterface;
use Keystone\Domain\Menu\Service\LinkResolver;
use Keystone\Core\Theme\ThemeManagerInterface;


final class PageController {
    public function __construct(
        private SeoService $seoService,
        private MenuRepositoryInterface $menuRepository,
        private ThemeManagerInterface $themes,
        private PageService $pages,
        private LinkResolver $linkResolver,
        private Twig $view
    ) {}

    public function homepage(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $page = $this->pages->getHomepage();

        $seo = $this->seoService->getForSubject(
    subject: new SeoSubject('page', $page->id()),
    fallbackTitle: $page->title(),
    fallbackDescription: mb_substr(strip_tags($page->content_html()), 0, 160),
    fallbackSlug: $page->slug(),
    baseUrl: 'https://keystone-cms.lan'
);

$header = $this->menuRepository->getByHandle('header');
$footer = $this->menuRepository->getByHandle('footer');

$template = $this->themes->resolvePageTemplate( $page->template() ?? 'default');



        return $this->view->render(
            $response,
            $template,
            [
            'page' => $page,
            'header' => $header,
            'footer' => $footer,
            'linkResolver' => $this->linkResolver,
            'seo' => $seo 
            ]
        );
    }


    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

$page = $this->pages->findBySlug($args['slug']);

        if (!$page) {
            throw new HttpNotFoundException($request);
        }

$seo = $this->seoService->getForSubject(
    subject: new SeoSubject('page', $page->id()),
    fallbackTitle: $page->title(),
    fallbackDescription: mb_substr(strip_tags($page->content_html()), 0, 160),
    fallbackSlug: $page->slug(),
    baseUrl: 'https://keystone-cms/lan'
);


$resolvedLinks = [];

foreach ($links as $link) {
    if ($link->to()->type() === 'page') {
        $targetPage = $this->pageService->getById(
            $link->to()->id()
        );

        $resolvedLinks[] = [
            'url' => $this->basePath . '/' . $targetPage->slug(),
            'anchor' => $link->anchorText(),
            'nofollow' => $link->nofollow(),
        ];
    }
}

$template = $this->themes->resolvePageTemplate( $page->template() ?? 'default');

return $this->view->render(
            $response,
            $template,
            [
                'page' => $page,
                'seo'  => $seo,
            ]
        );
}


private function InternalLinks(int $page_id) {

    $subject = new LinkSubject('page', $page_id);

    return $this->internalLinkService->getLinksFrom($subject);
    }
}
?>