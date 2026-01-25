<?php

use Keystone\Plugin\Pages\Controller\Admin\PageController;
use Keystone\Http\Middleware\AuthMiddleware;
use Keystone\Http\Middleware\CsrfMiddleware;
use Keystone\Http\Middleware\PolicyMiddleware;
use Keystone\Plugin\Pages\Controller\Admin\PageMediaController;
use Keystone\Plugin\Pages\Controller\Admin\PageVersionController;

$app->group('/admin/pages', function ($group) {
$group->get('', [PageController::class , 'index'])->setName('admin.pages.index');
$group->get('/create',[ PageController::class , 'create'])->setName('admin.pages.create');
$group->post('/create',[ PageController::class , 'save']);
$group->get('/{id}/edit-richtext',[PageController::class, 'editRichtext']);
$group->get('/{id}/edit-blocks',[PageController::class, 'editBlocks']);
$group->post('/{id:\d+}/save', [ PageController::class , 'save']);
$group->post('/{id:\d+}/delete',[ PageController::class , 'delete']);
$group->get('/{id}/publish', [PageController::class, 'publish']);
$group->post('/{id}/publish', [PageController::class, 'publishPost']);
$group->post('/{id}/unpublish', [PageController::class, 'unpublish']);
$group->post('/{id}/schedule',[PageController::class, 'schedule']);
$group->post('/{id}/homepage', [PageController::class, 'setHomepage']);
$group->get('/{id}/media',[PageMediaController::class, 'picker']);
$group->post('/{id}/media/{mediaId}',[PageMediaController::class, 'attach']);
$group->post('/{id}/media/{mediaId}/remove',[PageMediaController::class, 'detach']);
$group->get('/{id}/versions',[PageVersionController::class, 'index']);
$group->post('/{id}/versions/{versionId}/revert',[PageVersionController::class, 'revert']);
$group->post('/{id}/autosave',[PageController::class, 'autosave']);
})
->add($container->get(CsrfMiddleware::class))
->add($container->get(AuthMiddleware::class));

?>