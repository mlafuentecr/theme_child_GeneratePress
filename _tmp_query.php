<?php
// Temporary debug script — delete after use
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
define('ABSPATH', 'C:/Users/mlafu/Local Sites/test/app/public/');
require_once 'C:/Users/mlafu/Local Sites/test/app/public/wp-load.php';

global $wpdb;

$rows = $wpdb->get_results(
    "SELECT ID, post_type, post_title, post_content
     FROM {$wpdb->posts}
     WHERE post_content LIKE '%logo-strip%'
       AND post_status = 'publish'
     LIMIT 10"
);

foreach ($rows as $row) {
    echo "=== ID:{$row->ID} [{$row->post_type}] {$row->post_title}\n";
    echo substr($row->post_content, 0, 1200) . "\n\n";
}
echo "Total: " . count($rows) . "\n";
