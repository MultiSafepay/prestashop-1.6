<?php
/**
 * MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com>
 * @copyright Copyright (c) 2013 MultiSafepay (https://www.multisafepay.com)
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultiSafepayGenericGateway2ValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->type = 'redirect';
        $this->gateway = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_2_CODE');
        $this->gatewayTitle = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_2_TITLE');

        parent::postProcess();
    }
}
