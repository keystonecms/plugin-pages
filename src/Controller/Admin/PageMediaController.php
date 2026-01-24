<?php

namespace Keystone\Plugins\Pages\Controller\Admin;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Keystone\Plugins\Pages\Domain\PageService;
use Keystone\Plugins\Media\Domain\MediaService;

final class PageMediaController {
    public function __construct(
        private PageService $pages,
        private MediaService $media,
        private Twig $twig
    ) {}

public function detach(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $this->pages->detachMedia(
        (int) $args['id'],
        (int) $args['mediaId']
    );

    return $response
        ->withHeader('Location', "/admin/pages/{$args['id']}/edit")
        ->withStatus(302);
}



    public function picker(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $pageId = (int) $args['id'];

        return $this->twig->render(
            $response,
            '@media/admin/media/media-picker.twig',
            [
                'pageId' => $pageId,
                'media' => $this->media->all()
            ]
        );
    }

    public function attach(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $this->pages->attachMedia(
            (int) $args['id'],
            (int) $args['mediaId']
        );

        return $response
            ->withHeader('Location', "/admin/pages/{$args['id']}/edit")
            ->withStatus(302);
    }
}


?>