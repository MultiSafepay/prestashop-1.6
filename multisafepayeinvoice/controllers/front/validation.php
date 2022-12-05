<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayEinvoiceValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->api = Configuration::get('MULTISAFEPAY_API_KEY');
        $this->mode = Configuration::get('MULTISAFEPAY_SANDBOX');
        $this->getGatewayInfo();
        $this->type = 'direct';

        parent::postProcess();
    }

    private function getGatewayInfo()
    {
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $this->gatewayInfo = [
            'referrer' => $_SERVER['HTTP_REFERER'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'birthday' => Tools::getValue('birthday'),
            'bankaccount' => Tools::getValue('bankaccount'),
            'phone' => Tools::getValue('phone'),
            'email' => $customer->email,
            'gender' => '',
        ];
    }
}
