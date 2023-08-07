<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayZiniaValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->type = 'direct';
        $this->gatewayInfo = [
            'birthday' => Tools::getValue('birthday'),
            'phone' => Tools::getValue('phone'),
            'email' => Tools::getValue('email'),
            'gender' => Tools::getValue('gender'),
        ];

        parent::postProcess();
    }
}
