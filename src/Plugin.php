<?php

namespace Detain\MyAdminServers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Dedicated Servers Module';
	public static $description = 'Allows selling of Dedicated Servers Module';
	public static $help = '';
	public static $module = 'servers';
	public static $type = 'module';


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
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_servers', 'Out Of Stock Servers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_SERVERS'), array('0', '1'), array('No', 'Yes', ));
	}
}
