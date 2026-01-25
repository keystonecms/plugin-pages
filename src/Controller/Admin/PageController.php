<?php
# test update
declare(strict_types=1);

namespace Keystone\Plugin\Pages\Controller\Admin;

use Keystone\Domain\User\CurrentUser;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Keystone\Core\Http\Exception\ForbiddenException;
use Keystone\Plugin\Pages\Domain\PageService;
use Keystone\Http\Controllers\BaseController;
use Keystone\Security\CsrfToken;
use Keystone\Core\Auth\AuthorityActivityService;

use Keystone\Plugin\InternalLinks\Domain\InternalLinkService;
use Keystone\Plugin\InternalLinks\Domain\LinkSubject;

final class PageController extends BaseController {

    public function __construct(
        private PageService $pages,
        private CurrentUser $currentUser,
        private CsrfToken $token,
        private InternalLinkService $internalLinks,
        private Twig $view,
        private AuthorityActivityService $authority
    ) {}

public function schedule(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $data = $request->getParsedBody();

    $this->pages->schedulePublish(
        (int) $args['id'],
        (int) $data['version_id'],
        new \DateTimeImmutable($data['publish_at'])
    );

    return $this->json($response, [
        'status'  => 'success',
        'message' => 'Publish scheduled'
    ]);
}

public function publish(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $data = $request->getParsedBody();


        $page = $this->pages->findById((int) $args['id']);

        return $this->view->render(
            $response,
            '@pages/admin/publish.twig',
            [
                'page' => $page,
            ]
        );
    }

public function publishPost(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $data = $request->getParsedBody();

        $this->pages->publish((int) $args['id'], (int) $data['version_id']);
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    }

public function unpublish(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $this->pages->unpublish((int) $args['id']);
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    }

public function setHomepage(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $this->pages->setHomepage((int) $args['id']);
        return $response->withHeader('Location', '/admin/pages')->withStatus(302);
    }

public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface {
        $user = $this->currentUser->user();

        return $this->view->render(
            $response,
            '@pages/admin/index.twig',
            [
                'pages' => $this->pages->all(),
            ]
        );
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface {

            return $this->view->render(
            $response,
            '@pages/admin/create.twig'
            );
    }

public function editRichtext(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    $page = $this->pages->findById((int) $args['id']);

    if ($page === null) {
        throw new HttpNotFoundException($request, 'Page not found');
    }

        $blocks = $page->blocks();

    return $this->view->render($response, '@pages/admin/edit-richtext.twig', [
        'page' => $page,
        'mode' => 'richtext'
    ]);
}

public function editBlocks(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    $page = $this->pages->findById((int) $args['id']);

    if ($page === null) {
        throw new HttpNotFoundException($request, 'Page not found');
    }

    return $this->view->render($response, '@pages/admin/edit-blocks.twig', [
        'page' => $page,
        'mode' => 'blocks',
        'initialBlocks' => $page->blocks()
    ]);
}

public function delete(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $id = (int) $args['id'];

    $this->pages->delete($id);

    return $response
        ->withHeader('Location', '/admin/pages')
        ->withStatus(302);
}

public function autosave(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $data = $request->getParsedBody();

    $versionId = $this->pages->autosave(
        (int) $args['id'],
        (array) $data
    );

    return $this->json($response, [
        'status'    => 'success',
        'versionId' => $versionId,
        'savedAt'   => date('H:i'),
        'csrfToken' => htmlspecialchars($this->token->generate(), ENT_QUOTES),
    ]);
}



public function save(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {

    $data = $request->getParsedBody();

    $blocks = [];

    if (
        isset($data['blocks']) &&
        is_string($data['blocks']) &&
        $data['blocks'] !== ''
    ) {
        $blocks = json_decode($data['blocks'], true, 512, JSON_THROW_ON_ERROR);
    }

    $payload = [
        'id'           => (int) $args['id'],
        'title'        => trim($data['title'] ?? ''),
        'slug'         => trim($data['slug'] ?? $data['title']),
        'status'       => $data['status'] ?? 'draft',
        'authorId'     => $this->currentUser->user()->id(),
        'template'     => $data['template'] ?? null,
        'content_mode' => $data['content_mode'],
        'content_html' => $data['content_html'] ?? null,
        'blocks'       => $blocks,
    ];

    $user = $this->currentUser->user();
    
    try {
        $this->pages->save($payload);

        $this->internalLinks->syncLinksForSubject(
            new LinkSubject('page', (int) $args['id']),
            $data['internal_links'] ?? []
        );

        $this->authority->page($this->currentUser->user()->id(), $payload['title'], $user->email(), $_SERVER['REMOTE_ADDR']);

        return $this->json($response, [
            'status'    => 'success',
            'message'   => 'Page saved ' . date('Y-m-d H:i:s'),
            'csrfToken' => htmlspecialchars($this->token->generate(), ENT_QUOTES),
        ]);

    } catch (\RuntimeException $e) {
        return $this->json($response, [
            'status'  => 'error',
            'message' => $e->getMessage()
        ]);
    }
}



}
