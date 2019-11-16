<?php

namespace Detain\MyAdminServers;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminServers
 */
class Plugin
{
	public static $name = 'Dedicated Servers';
	public static $description = 'Allows selling of Dedicated Servers Module';
	public static $help = '';
	public static $module = 'servers';
	public static $type = 'module';
	public static $settings = [
		'SERVICE_ID_OFFSET' => 4000,
		'USE_REPEAT_INVOICE' => true,
		'USE_PACKAGES' => false,
		'BILLING_DAYS_OFFSET' => 0,
		'IMGNAME' => 'stack.png',
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

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
			self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}


	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		myadmin_log(self::$module, 'info', 'Dedicated Server Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
		$event->stopPropagation();
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function loadProcessing(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $service
		 */
		$service = $event->getSubject();
		$service->setModule(self::$module)
			->setEnable(function ($service) {
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active-billing' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active-billing', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				admin_email_server_pending_setup($serviceInfo[$settings['PREFIX'].'_id']);
			})->setReactivate(function ($service) {
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active-billing', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$smarty = new \TFSmarty;
				$smarty->assign('server_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/server_reactivated.tpl');
				$subject = $serviceInfo[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Reactivated';
				(new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/server_reactivated.tpl');
			})->setDisable(function () {
			})->register();
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, _('General'), 'outofstock_servers', _('Out Of Stock Servers'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_SERVERS'), ['0', '1'], ['No', 'Yes']);
	}
}
