<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayNationaletuinbonValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->api = Configuration::get('MULTISAFEPAY_NATIONALETUINBON_API_KEY');
        $this->mode = Configuration::get('MULTISAFEPAY_NATIONALETUINBON_SANDBOX');
        $this->gatewayInfo = '';

        parent::postProcess();
    }
}
