#!/usr/bin/env php
<?php
include_once __DIR__.'/../../include/functions.inc.php';
$db = clone $GLOBALS['tf']->db;
$db2 = clone $GLOBALS['tf']->db;
$db->query('select * from switchports where server_id is not null;', __LINE__, __FILE__);
while ($db->next_record(MYSQL_ASSOC)) {
    $db2->query("select * from assets where order_id={$db->Record['server_id']}", __LINE__, __FILE__);
    if ($db2->num_rows() > 0) {
        $db2->next_record(MYSQL_ASSOC);
        if ($db->Record['asset_id'] != $db2->Record['id']) {
            echo "switchport {$db->Record['switchport_id']} server id {$db->Record['server_id']} asset id {$db->Record['asset_id']} != {$db2->Record['id']}\n";
            $db2->query("update switchports set asset_id={$db2->Record['id']} where switchport_id={$db->Record['switchport_id']}", __LINE__, __FILE__);
        }
    }
}
