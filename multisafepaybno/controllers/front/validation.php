<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayBnoValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        if (Configuration::get('MULTISAFEPAY_BNO_DIRECT')) {
            $this->type = 'direct';
            $this->getGatewayInfo();
        } else {
            $this->type = 'redirect';
            $this->gatewayInfo = '';
        }

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

        if (Tools::getValue('bankaccount') != '') {
            $this->context->cookie->__set('bankaccount', Tools::getValue('bankaccount'));
        }
        if (Tools::getValue('phone') != '') {
            $this->context->cookie->__set('phone', Tools::getValue('phone'));
        }
        if (Tools::getValue('birthday') != '') {
            $this->context->cookie->__set('birthday', Tools::getValue('birthday'));
        }

        $this->gatewayInfo = [
            'referrer' => $_SERVER['HTTP_REFERER'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'birthday' => $this->context->cookie->birthday,
            'bankaccount' => $this->context->cookie->bankaccount,
            'phone' => $this->context->cookie->phone,
            'email' => $customer->email,
            'gender' => '',
        ];
    }
}
