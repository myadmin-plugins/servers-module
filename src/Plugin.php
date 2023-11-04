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
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
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
    public static function getDeactivate(GenericEvent $event)
    {
        $serviceTypes = run_event('get_service_types', false, self::$module);
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // add deactivation logic here
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
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
                admin_email_server_pending_setup($serviceInfo[$settings['PREFIX'].'_id']);
            })->setReactivate(function ($service) {
                $serviceTypes = run_event('get_service_types', false, self::$module);
                $serviceInfo = $service->getServiceInfo();
                $settings = get_module_settings(self::$module);
                $db = get_module_db(self::$module);
                $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
                $smarty = new \TFSmarty();
                $smarty->assign('server_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
                $email = $smarty->fetch('email/admin/server_reactivated.tpl');
                $subject = $serviceInfo[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Reactivated';
                (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/server_reactivated.tpl');
                function_requirements('setServerStatus');
                setServerStatus($serviceInfo[$settings['PREFIX'].'_id'], 'active');
            })->setDisable(function ($service) {
                $serviceInfo = $service->getServiceInfo();
                $settings = get_module_settings(self::$module);
                function_requirements('setServerStatus');
                setServerStatus($serviceInfo[$settings['PREFIX'].'_id'], 'suspended');
            })->setTerminate(function ($service) {
                $serviceInfo = $service->getServiceInfo();
                $settings = get_module_settings(self::$module);
                $serviceTypes = run_event('get_service_types', false, self::$module);
                /*
                if ($serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_type'] == get_service_define('MAIL_ZONEMTA')) {
                    $class = '\\MyAdmin\\Orm\\'.get_orm_class_from_table($settings['TABLE']); */
                    /** @var \MyAdmin\Orm\Product $class **/
                    /*$serviceClass = new $class();
                    $serviceClass->load_real($serviceInfo[$settings['PREFIX'].'_id']);
                    $subevent = new GenericEvent($serviceClass, [
                        'field1' => $serviceTypes[$serviceClass->getType()]['services_field1'],
                        'field2' => $serviceTypes[$serviceClass->getType()]['services_field2'],
                        'type' => $serviceTypes[$serviceClass->getType()]['services_type'],
                        'category' => $serviceTypes[$serviceClass->getType()]['services_category'],
                        'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid())
                    ]);
                    $success = true;
                    try {
                        $GLOBALS['tf']->dispatcher->dispatch($subevent, self::$module.'.terminate');
                    } catch (\Exception $e) {
                        myadmin_log('myadmin', 'error', 'Got Exception '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                        $subject = 'Cant Connect to DB to Reactivate';
                        $email = $subject.'<br>Username '.$serviceClass->getUsername().'<br>'.$e->getMessage();
                        (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/website_connect_error.tpl');
                        $success = false;
                    }
                    if ($success == true && !$subevent->isPropagationStopped()) {
                        myadmin_log(self::$module, 'error', 'Dont know how to deactivate '.$settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Type '.$serviceTypes[$serviceClass->getType()]['services_type'].' Category '.$serviceTypes[$serviceClass->getType()]['services_category'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                        $success = false;
                    }
                    if ($success == true) {
                        $serviceClass->setServerStatus('deleted')->save();
                        $GLOBALS['tf']->history->add($settings['TABLE'], 'change_server_status', 'deleted', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
                    }
                } else {
                    $GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'destroy', '', $serviceInfo[$settings['PREFIX'].'_custid']);
                }*/
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
