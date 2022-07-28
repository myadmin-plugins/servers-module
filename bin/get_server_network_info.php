#!/usr/bin/env php
<?php
include __DIR__.'/../../include/functions.inc.php';

if (!isset($_SERVER['argv'][1])) {
    die('Missing Server ID Parameter!

Syntax: '.$_SERVER['argv'][0].' <id>
 where <id> is a server id

Example Response:
{
    "vlans": {
        "6897": {
            "network": "66.45.252.40/29",
            "network_ip": "66.45.252.40",
            "bitmask": "29",
            "netmask": "255.255.255.248",
            "broadcast": "66.45.252.47",
            "hostmin": "66.45.252.41",
            "hostmax": "66.45.252.46",
            "first_usable": "66.45.252.42",
            "gateway": "66.45.252.41",
            "hosts": 6,
            "vlans_id": "6897",
            "vlans_block": "1",
            "vlans_networks": ":66.45.252.40/29:",
            "vlans_ports": ":139/Ethernet1/23:",
            "vlans_comment": "fullrack.ripcordindustries.com",
            "vlans_primary": "1",
            "primary": true,
            "comment": "fullrack.ripcordindustries.com"
        }
    },
    "assets": {
        "4221": {
            "id": "4221",
            "order_id": "14157",
            "datacenter": "04",
            "type_id": "1",
            "asset_tag": "",
            "rack": "",
            "row": "016",
            "col": "12",
            "unit_start": "1",
            "unit_end": "48",
            "unit_sub": "0",
            "ipmi_mac": "",
            "ipmi_ip": "",
            "hostname": "fullrack.ripcordindustries.com",
            "status": "active",
            "company": "int",
            "comments": "",
            "make": "Eaton",
            "model": "",
            "description": "",
            "primary_ipv4": "",
            "primary_ipv6": "",
            "customer_id": "int3000",
            "external_id": "",
            "billing_status": "unknown",
            "overdue": "0",
            "create_timestamp": null,
            "update_timestamp": null,
            "asset_id": "1",
            "asset_name": "server",
            "switchports": [
                "8148"
            ],
            "vlans": null
        }
    },
    "switchports": {
        "8148": {
            "switchport_id": "8148",
            "switch_id": "139",
            "switch": "edge6a",
            "port": "Ethernet1/23",
            "blade": "Ethernet1",
            "justport": "23",
            "graph_id": "10395",
            "vlans": [
                "6897"
            ],
            "asset_id": "4221"
        }
    }
}
');
}
$info = get_server_network_info($_SERVER['argv'][1]);
echo str_replace('\\/', '/', json_encode($info, JSON_PRETTY_PRINT)).PHP_EOL;
