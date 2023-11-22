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
    if (isset($_SERVER['argv'])) {
        $GLOBALS['tf']->session->create(160307, 'services', false, 0, false, substr(basename($_SERVER['argv'][0], '.php'), 0, 32));
    }
    if (!isset($_SERVER['SSH_CLIENT']) && $GLOBALS['tf']->ima !== 'admin' && $GLOBALS['tf']->accounts->data['ima'] !== 'admin') {
        die('You\'re not authorized');
    }
    $final = [];
    if (preg_match_all('/^lease ([\d\.]*) {.*hardware ethernet ([^;\n]*);?\n.*}/msuU', file_get_contents('http://162.250.127.210/dhcpd.leases'), $matches)) {
        foreach ($matches[1] as $idx => $ip) {
            $final[$matches[2][$idx]] = $ip;
        }
    }
    if (preg_match_all('/^lease ([\d\.]*) {.*hardware ethernet ([^;\n]*);?\n.*}/msuU', file_get_contents('http://216.219.95.21/dhcpd.leases'), $matches)) {
        foreach ($matches[1] as $idx => $ip) {
            $final[$matches[2][$idx]] = $ip;
        }
    }
    //myadmin_log('myadmin', 'debug', json_encode($final), __LINE__, __FILE__);
    //myadmin_log('myadmin', 'debug', json_encode($final[strtolower($GLOBALS['tf']->variables->request['ipmi_mac'])]), __LINE__, __FILE__);
    if (count($final) > 0) {
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
            $ip_val = $final[strtolower($GLOBALS['tf']->variables->request['ipmi_mac'])] ?? '';
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
