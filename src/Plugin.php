<?php

namespace Detain\MyAdminServers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Dedicated Servers';
	public static $description = 'Allows selling of Dedicated Servers Module';
	public static $help = '';
	public static $module = 'servers';
	public static $type = 'module';
	public static $settings = [
		'SERVICE_ID_OFFSET' => 4000,
		'USE_REPEAT_INVOICE' => TRUE,
		'USE_PACKAGES' => FALSE,
		'BILLING_DAYS_OFFSET' => 0,
		'IMGNAME' => 'vcard_48.png',
		'REPEAT_BILLING_METHOD' => PRORATE_BILLING,
		'DELETE_PENDING_DAYS' => 45,
		'SUSPEND_DAYS' => 14,
		'SUSPEND_WARNING_DAYS' => 7,
		'TITLE' => 'Dedicated Servers',
		'MENUNAME' => 'Servers',
		'EMAIL_FROM' => 'support@interserver.net',
		'TBLNAME' => 'Servers',
		'TABLE' => 'servers',
		'TITLE_FIELD' => 'server_hostname',
		'PREFIX' => 'server'];


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function loadProcessing(GenericEvent $event) {

	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_servers', 'Out Of Stock Servers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_SERVERS'), ['0', '1'], ['No', 'Yes']);
	}
}
