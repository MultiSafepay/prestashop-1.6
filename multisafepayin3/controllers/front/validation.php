<?php
/**
 * MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com.com>
 * @copyright Copyright (c) 2020 MultiSafepay (https://www.multisafepay.com)
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayIn3ValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->type = 'direct';
        $this->getGatewayInfo();
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

        if (Tools::getValue('phone') != '') {
            $this->context->cookie->__set('phone', Tools::getValue('phone'));
        }

        $this->gatewayInfo = [
            'referrer' => $_SERVER['HTTP_REFERER'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'phone' => $this->context->cookie->phone ?: '',
        ];
    }
}
