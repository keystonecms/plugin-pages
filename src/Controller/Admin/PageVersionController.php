<?php

namespace Keystone\Plugins\Pages\Controller\Admin;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Keystone\Plugins\Pages\Domain\PageService;
use Keystone\Http\Controllers\BaseController;

final class PageVersionController extends BaseController {
    public function __construct(
        private PageService $pages,
        private Twig $twig
    ) {}

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $pageId = (int) $args['id'];

        $page = $this->pages->findById((int) $pageId);

        return $this->twig->render(
            $response,
            '@pages/admin/versions.twig',
            [
                'page'   => $page,
                'versions' => $this->pages->versions($pageId),
            ]
        );
    }

    public function revert(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        try {
            $this->pages->revertToVersion(
                (int) $args['id'],
                (int) $args['versionId']
            );

            return $this->json($response, [
                'status'  => 'success',
                'message' => 'Page reverted'
            ]);

        } catch (\RuntimeException $e) {
            return $this->json($response, [
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

?>