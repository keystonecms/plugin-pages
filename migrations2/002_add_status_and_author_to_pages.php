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
    ALTER TABLE pages
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft',
    ADD COLUMN author_id INT NOT NULL
SQL,

  'up' => <<<SQL
    UPDATE pages
    SET status = 'published'
    WHERE status IS NULL OR status = '';
SQL,

  'up' => <<<SQL
    UPDATE pages
    SET author_id = 1
    WHERE author_id = 0;
SQL,

 'down' => <<<SQL

SQL
];

?>
