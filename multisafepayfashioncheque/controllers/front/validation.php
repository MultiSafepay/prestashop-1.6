<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayFashionChequeValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->api = Configuration::get('MULTISAFEPAY_FASHIONCHEQUE_API_KEY');
        $this->mode = Configuration::get('MULTISAFEPAY_FASHIONCHEQUE_SANDBOX');
        $this->gatewayInfo = '';

        parent::postProcess();
    }
}
