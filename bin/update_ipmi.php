<?php
/**
* Updates the IPMI information on servers
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Servers
* @copyright 2020
*/

function update_ipmi_ip()
{
	require_once __DIR__.'/../../../../include/functions.inc.php';
    if (isset($_SERVER['argv']))
	    $GLOBALS['tf']->session->create(160307, 'services', false, 0, false, substr(basename($_SERVER['argv'][0], '.php'), 0, 32));
	if (!isset($_SERVER['SSH_CLIENT']) && $GLOBALS['tf']->ima !== 'admin' && $GLOBALS['tf']->accounts->data['ima'] !== 'admin') {
		die('You\'re not authorized');
	}
	if ($stream = fopen('http://162.250.127.210/dhcpd.leases', 'rb')) {
		stream_get_contents($stream, true);
		$final = [];
		while ($line = fgets($stream)) {
			flush();
			if (strpos($line, 'lease ') !== false) {
				$temp_lease = substr($line, 6);
				$ip = substr(trim($temp_lease), 0, -2);
			}
			if (strpos($line, 'hardware ethernet ') !== false) {
				$temp_mac = substr($line, 20);
				$mac = substr(trim($temp_mac), 0, -1);
				$final[$mac] = $ip;
			}
		}
		$db = clone $GLOBALS['tf']->db;
		if (isset($_SERVER['SSH_CLIENT'])) {
			$update = 0;
			foreach ($final as $mac_key => $ip_val) {
				$db->query("SELECT * FROM assets WHERE ipmi_mac = '{$mac_key}' ORDER BY id DESC LIMIT 1");
				if ($db->num_rows() > 0) {
					$db->next_record(MYSQL_ASSOC);
					$server_id = $db->Record['id'];
					$db->query("UPDATE assets SET ipmi_ip = '{$ip_val}' WHERE id={$db->Record['id']}");
					++$update;
				}
			}
			echo $update.' row(s) updated.'.PHP_EOL;
		} elseif (isset($GLOBALS['tf']->variables->request['ipmi_mac'])) {
			$ip_val = $final[$GLOBALS['tf']->variables->request['ipmi_mac']] ?? '';
			$mac_key = $GLOBALS['tf']->variables->request['ipmi_mac'];
			if ($mac_key) {
				$db->query("SELECT * FROM assets WHERE ipmi_mac = '{$mac_key}' ORDER BY id DESC LIMIT 1");
				if ($db->num_rows() > 0) {
					$db->next_record(MYSQL_ASSOC);
					$server_id = $db->Record['id'];
					$db->query("UPDATE assets SET ipmi_ip = '{$ip_val}' WHERE id={$db->Record['id']}");
				}
			}
			echo $ip_val;
			exit;
		}
	}
}
if (isset($_SERVER['SSH_CLIENT'])) {
	update_ipmi_ip();
}
