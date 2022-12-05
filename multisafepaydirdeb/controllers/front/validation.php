<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayDirdebValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        if (Tools::getValue('direct')) {
            $accountIban = Tools::getValue('accountiban');

            $this->type = 'direct';
            $this->gatewayInfo = [
                'account_id' => $accountIban,
                'account_holder_name' => Tools::getValue('accountholder'),
                'account_holder_iban' => $accountIban,
                'emandate' => Tools::getValue('emandate'),
            ];
        }

        parent::postProcess();
    }
}
