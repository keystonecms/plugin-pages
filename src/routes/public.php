<?php

use Keystone\Plugins\Pages\Controller\Public\PageController;
use Keystone\Plugins\Pages\Controller\Public\PagePreviewController;

$app->get('/', PageController::class . ':homepage');
// $app->get('/{slug}', PageController::class . ':show');
$app->get('/preview/pages/{pageId}/versions/{versionId}',[PagePreviewController::class, 'preview']);

?>



