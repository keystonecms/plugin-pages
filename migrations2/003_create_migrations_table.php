<?php

declare(strict_types=1);

/*
 * Keystone CMS
 *
 * @package   Keystone CMS
 * @license   MIT
 * @link      https://keystone-cms.com
 */

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    executed_at DATETIME NOT NULL,
    UNIQUE KEY uniq_plugin_version (plugin, version)
);
SQL,

    'down' => <<<SQL
DROP TABLE migrations;
SQL
];