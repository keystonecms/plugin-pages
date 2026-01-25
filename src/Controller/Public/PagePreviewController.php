<?php

namespace Keystone\Plugin\Pages\Controller\Public;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Keystone\Plugins\Pages\Domain\PageService;

final class PagePreviewController {
    public function __construct(
        private PageService $pages,
        private Twig $twig
    ) {}

public function preview(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $page = $this->pages->findById($args['pageId']);

   if (!$page) {
        return $response->withStatus(404);
    }

    $version = $this->version->find(
        $page['published_version_id']
    );

    return $this->twig->render(
        $response,
        $version['template'],
        ['page' => $version]
    );
}

}

?>
