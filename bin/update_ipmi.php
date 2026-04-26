<?php
/**
* Updates the IPMI information on servers
*
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category Servers
* @copyright 2025
*/

function update_ipmi_ip()
{
    require_once __DIR__.'/../../../../include/functions.inc.php';
    if (isset($_SERVER['argv'])) {
        \MyAdmin\App::session()->create(160307, 'services', false, 0, false, substr(basename($_SERVER['argv'][0], '.php'), 0, 32));
    }
    if (!isset($_SERVER['SSH_CLIENT']) && \MyAdmin\App::ima() !== 'admin' && \MyAdmin\App::accounts()->data['ima'] !== 'admin') {
        die('You\'re not authorized');
    }
    $final = [];
    $db = get_module_db('default');
    $db->query("select * from ipmi_hosts", __LINE__, __FILE__);
    while ($db->next_record(MYSQL_ASSOC)) {
        $dhcpHost = $db->Record['host_ip'];
        if (preg_match_all('/^lease (?P<ip>[\d\.]*) {.*ends (?P<dayofweek>\d+) (?P<year>\d+)\/(?P<month>\d+)\/(?P<day>\d+) (?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+);.* ethernet (?P<mac>[a-f\d:]+)\s*;.*}/msuU', file_get_contents('http://'.$dhcpHost.'/dhcpd.leases'), $matches)) {
            foreach ($matches['mac'] as $idx => $mac) {
                // Parse the lease timestamp
                $timestamp = mktime($matches['hour'][$idx], $matches['minute'][$idx], $matches['second'][$idx], $matches['month'][$idx], $matches['day'][$idx], $matches['year'][$idx]);
                // Check if this MAC address is already in the final array
                if (!isset($final[$mac]) || $final[$mac]['timestamp'] < $timestamp) {
                    // Store the IP and timestamp for the most recent lease
                    $final[$mac] = [
                        'ip' => $matches['ip'][$idx],
                        'timestamp' => $timestamp
                    ];
                }
            }
        }
    }
    //myadmin_log('myadmin', 'debug', json_encode($final), __LINE__, __FILE__);
    //myadmin_log('myadmin', 'debug', json_encode($final[strtolower(\MyAdmin\App::variables()->request['ipmi_mac'])]), __LINE__, __FILE__);
    if (count($final) > 0) {
        $db = clone \MyAdmin\App::db();
        if (isset($_SERVER['SSH_CLIENT'])) {
            $update = 0;
            foreach ($final as $mac_key => $ipData) {
                $ip_val = $ipData['ip'];
                $db->query("SELECT * FROM assets WHERE ipmi_mac = '{$mac_key}' ORDER BY id DESC LIMIT 1");
                if ($db->num_rows() > 0) {
                    $db->next_record(MYSQL_ASSOC);
                    $server_id = $db->Record['id'];
                    $db->query("UPDATE assets SET ipmi_ip = '{$ip_val}' WHERE id={$db->Record['id']}");
                    ++$update;
                }
            }
            echo $update.' row(s) updated.'.PHP_EOL;
        } elseif (isset(\MyAdmin\App::variables()->request['ipmi_mac'])) {
            $ip_val = $final[strtolower(\MyAdmin\App::variables()->request['ipmi_mac'])]['ip'] ?? '';
            $mac_key = \MyAdmin\App::variables()->request['ipmi_mac'];
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
