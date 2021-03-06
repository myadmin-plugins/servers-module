#!/usr/bin/env php
<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
$GLOBALS['tf']->session->create(160307, 'services', false, 0, false, substr(basename($_SERVER['argv'][0], '.php'), 0, 32));
$gb = 1073741824;
ini_set('memory_limit', 4*$gb);
$db = clone $GLOBALS['tf']->db;
$db->query('select count(*) as total_legacy_invoices from invoices where invoices_description="Legacy Billing Balance" and invoices_type < 10', __LINE__, __FILE__);
$db->next_record(MYSQL_ASSOC);
$count = $db->Record['total_legacy_invoices'];
$old_count = file_get_contents('/home/my/logs/legacy_counts.txt');
if ($count < $old_count) {
	$smarty = new TFSmarty();
	$smarty->assign('old_count', $old_count);
	$smarty->Assign('count', $count);
	$msg = $smarty->fetch('email/admin/legacy_invoices.tpl');
	echo "{$msg}\n";
	myadmin_log('servers', 'warning', $msg, __LINE__, __FILE__);
	(new \MyAdmin\Mail())->adminMail('Legacy Billing Invoice Error!', $msg, 'my@interserver.net', 'admin/legacy_invoices.tpl');
}
file_put_contents('/home/my/logs/legacy_counts.txt', $count);
