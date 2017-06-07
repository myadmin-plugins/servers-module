<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_servers define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'MyAdmin Dedicated Servers Module for MyAdmin',
	'description' => 'Allows selling of Dedicated Servers Module',
	'help' => '',
	'module' => 'servers',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-servers-module',
	'repo' => 'https://github.com/detain/myadmin-servers-module',
	'version' => '1.0.0',
	'type' => 'module',
	'hooks' => [
		'servers.load_processing' => ['Detain\MyAdminServers\Plugin', 'Load'],
		'servers.settings' => ['Detain\MyAdminServers\Plugin', 'Settings'],
		/* 'function.requirements' => ['Detain\MyAdminServers\Plugin', 'Requirements'],
		'servers.activate' => ['Detain\MyAdminServers\Plugin', 'Activate'],
		'servers.change_ip' => ['Detain\MyAdminServers\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminServers\Plugin', 'Menu'] */
	],
];
