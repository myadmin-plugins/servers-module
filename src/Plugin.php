<?php

namespace Detain\MyAdminServers;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {

	}

	public static function Settings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('servers', 'General', 'outofstock_servers', 'Out Of Stock Servers', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_SERVERS'), array('0', '1'), array('No', 'Yes', ));
	}
}
