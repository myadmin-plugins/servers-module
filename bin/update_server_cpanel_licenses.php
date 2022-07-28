#!/usr/bin/env php
<?php
include_once __DIR__.'/../../include/functions.inc.php';
include_once __DIR__.'/../../vendor/detain/cpanel-licensing/src/Cpanel.php';
$db = clone $GLOBALS['tf']->db;
$db2 = clone $GLOBALS['tf']->db;
$license_type = 5008;
$license_cost = 25;
echo 'Building list of servers + main ips';
$db->query("select * from servers, assets where server_id=order_id order by server_status desc");
$ips = [];
while ($db->next_record(MYSQL_ASSOC)) {
    $serviceInfo = $db->Record;
    $networkInfo = get_server_network_info($serviceInfo['server_id']);
    $has_ip = false;
    if (count($networkInfo['vlans']) > 0) {
        foreach ($networkInfo['vlans'] as $vlanId => $vlanData) {
            if ($has_ip === false) {
                if ($vlanData['vlans_primary'] == 1) {
                    $has_ip = true;
                }
                $ip_address = $vlanData['first_usable'];
            }
        }
    }
    if ($has_ip == false && trim(str_replace('NULL', '', $serviceInfo['primary_ipv4'])) != '') {
        $ip_address = trim(str_replace('NULL', '', $serviceInfo['primary_ipv4']));
        $has_ip = true;
    }
    if ($has_ip == true) {
        $ips[$ip_address] = $serviceInfo;
    }
}
echo 'done'.PHP_EOL;
echo 'Getting list of cpanel licenses';
$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
$cpl->format = 'json';
$licenses = json_decode($cpl->fetchLicenses(), true);
echo 'done'.PHP_EOL;
$errors = [];
$good = 0;
$go =  false;
$settings = get_module_settings('licenses');
foreach ($licenses['licenses'] as $idx => $licenseData) {
    $ip = $licenseData['ip'];
    if ($licenseData['envtype'] == 'standard' && array_key_exists($ip, $ips)) {
        $db->query("select * from repeat_invoices where repeat_invoices_module='servers' and repeat_invoices_service={$ips[$ip]['server_id']}");
        if ($db->num_rows() > 0) {
            echo "{$ip} {$ips[$ip]['server_status']} {$ips[$ip]['server_id']} {$ips[$ip]['server_hostname']}";
            $good++;
            while ($db->next_record(MYSQL_ASSOC)) {
                echo ' repeat invoice #'.$db->Record['repeat_invoices_id'].' $'.$db->Record['repeat_invoices_cost'].' '.$db->Record['repeat_invoices_description'];
                $db2->query("select * from licenses where license_type='{$license_type}' and license_custid={$db->Record['repeat_invoices_custid']} and license_ip='{$ip}' and license_status='active'");
                echo 'Rows:'.$db2->num_rows().PHP_EOL;
                if ($db2->num_rows() > 0) {
                    echo '  Customer already has active INTERSERVER-INTERNAL license on that ip, skipping!'.PHP_EOL;
                    if ($ips[$ip]['server_cost'] != $db->Record['repeat_invoices_cost']) {
                        echo "{$ips[$ip]['server_cost']} != {$db->Record['repeat_invoices_cost']}\n";
                        /* $repeatInvoiceObj = new \MyAdmin\Orm\Repeat_Invoice();
                        $repeatInvoiceObj->load_real($db->Record['repeat_invoices_id']);
                        if ($repeatInvoiceObj->loaded === true) {
                            $repeatInvoiceObj->setCost($ips[$ip]['server_cost'])->save();
                        }*/
                    }
                } else {
                    $frequency = $db->Record['repeat_invoices_frequency'];
                    $cost = $license_cost;
                    $total_cost = $cost * $frequency;
                    if ($frequency > 1) {
                        // apply the repeat service price for the other months
                        if ($frequency >= 36) {
                            $total_cost = round($total_cost * 0.80, 2);
                        } elseif ($frequency >= 24) {
                            $total_cost = round($total_cost * 0.85, 2);
                        } elseif ($frequency >= 12) {
                            $total_cost = round($total_cost * 0.90, 2);
                        } elseif ($frequency >= 6) {
                            $total_cost = round($total_cost * 0.95, 2);
                        }
                    }
                    echo '  Adding License for '.$db->Record['repeat_invoices_custid'].' with cost of $'.$total_cost.'/'.$frequency.' month(s)'.PHP_EOL;
                    $now = mysql_now();
                    if ($go == true) {
                        $repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db2);
                        $repeat_invoice->setDescription($service_types[$service_type]['services_name'])
                            ->setType(1)
                            ->setCustid($db->Record['repeat_invoices_custid'])
                            ->setCost($total_cost)
                            ->setFrequency($frequency)
                            ->setDate($db->Record['repeat_invoices_date'])
                            ->setNextDate($db->Record['repeat_invoices_next_date'])
                            ->setModule('licenses')
                            ->save();
                        $rid = $repeat_invoice->get_id();
                        $db2->query(make_insert_query($settings['TABLE'], [
                            $settings['PREFIX'].'_id' => null,
                            $settings['PREFIX'].'_type' => $license_type,
                            $settings['PREFIX'].'_custid' => $db->Record['repeat_invoices_custid'],
                            $settings['PREFIX'].'_order_date' => $db->Record['repeat_invoices_date'],
                            $settings['PREFIX'].'_ip' => $ip,
                            $settings['PREFIX'].'_status' => 'active',
                            $settings['PREFIX'].'_invoice' => $rid,
                            $settings['PREFIX'].'_coupon' => 0,
                            $settings['PREFIX'].'_extra' => '',
                            $settings['PREFIX'].'_hostname' => ''
                        ]), __LINE__, __FILE__);
                        $serviceid = $db2->getLastInsertId($settings['TABLE'], $settings['PREFIX'].'_id');
                        $repeat_invoice->set_service($serviceid)->save();
                        echo '  Added License '.$serviceid.' and repeat_invoice '.$rid.PHP_EOL;
                    }
                    $new_cost = bcsub($db->Record['repeat_invoices_cost'], $total_cost, 2);
                    echo '  Updating Server Repeat Invoice cost to $'.$new_cost.PHP_EOL;
                    if ($go == true) {
                        $repeatInvoiceObj = new \MyAdmin\Orm\Repeat_Invoice();
                        $repeatInvoiceObj->load_real($db->Record['repeat_invoices_id']);
                        if ($repeatInvoiceObj->loaded === true) {
                            $repeatInvoiceObj->setCost($new_cost)->save();
                        }
                    }
                }
            }
            //echo PHP_EOL;
        } else {
            $errors[] = "{$ip} {$ips[$ip]['server_status']} {$ips[$ip]['server_id']} {$ips[$ip]['server_hostname']}";
        }
        //echo '  '.json_encode($licenseData).PHP_EOL;
    }
}
echo $good.PHP_EOL;
