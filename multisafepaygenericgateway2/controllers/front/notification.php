<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/notification.php';

class MultiSafepayGenericgateway2NotificationModuleFrontController extends MultisafepayNotificationModuleFrontController
{
    public function postProcess()
    {
        parent::postProcess();
    }
}
