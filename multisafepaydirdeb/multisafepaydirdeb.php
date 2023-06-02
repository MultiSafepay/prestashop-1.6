<?php
/**
 *  MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com>
 * @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

if (!Module::isInstalled('multisafepay')) {
    Tools::displayError('Before installing this module, you have to install the main module first!');

    return false;
}

require_once _PS_MODULE_DIR_ . 'multisafepay/api/Autoloader.php';
require_once _PS_MODULE_DIR_ . 'multisafepay/helpers/Autoloader.php';

class MultisafepayDirdeb extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepaydirdeb';
        $this->tab = 'payments_gateways';
        $this->version = '3.10.2';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];
        $this->dependencies = ['multisafepay'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->gateway = 'DIRDEB';
        $this->displayName = $this->l('Direct debit');
        $this->description = $this->l('This module allows you to accept payments by MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');

        if (Module::isInstalled('bvkpaymentfees') && Module::isInstalled('multisafepay')) {
            $this->payment_fee = new PaymentFee();
            $this->fee = $this->payment_fee->getFee($this->name);
        }
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('payment')) {
            return false;
        }

        Configuration::updateValue('MULTISAFEPAY_DIRDEB_DIRECT', true);

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_DIRDEB_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_DIRDEB_MAX_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_DIRDEB_DIRECT');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $min_amount = (int) Configuration::get('MULTISAFEPAY_DIRDEB_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_DIRDEB_MAX_AMOUNT');

        $direct = Configuration::get('MULTISAFEPAY_DIRDEB_DIRECT');

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

        if (($max_amount > 0 && $total > $max_amount) || ($min_amount >= 0 && $total < $min_amount) && ($min_amount != $max_amount)) {
            return false;
        }

        $this->context->smarty->clearAssign(MultiSafepay::SMARTY_VARIABLES_TO_UNASSIGN);

        $this->context->smarty->assign([
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'main_path_ssl' => _PS_ROOT_DIR_,
            'moduleLink' => $this->name,
            'gateway' => $this->gateway,
            'name' => $this->displayName,
            'fee' => $this->fee,
            'direct' => $direct,
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'accountholder',
                    'label' => 'Account holder name',
                    'required' => true,
                    'placeholder' => 'M. Safepay',
                ],
                [
                    'type' => 'text',
                    'name' => 'accountiban',
                    'label' => 'Account iban',
                    'required' => true,
                    'placeholder' => 'NL87ABNA0000000001',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'emandate',
                    'value' => 1,
                ],
            ],
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/multisafepay.css', 'all');
        $this->context->smarty->assign(['Multisafepay_module_dir' => _MODULE_DIR_ . $this->name]);
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            $min_amount = Tools::getValue('MULTISAFEPAY_DIRDEB_MIN_AMOUNT');
            $max_amount = Tools::getValue('MULTISAFEPAY_DIRDEB_MAX_AMOUNT');
            $direct = Tools::getValue('MULTISAFEPAY_DIRDEB_DIRECT');

            Configuration::updateValue('MULTISAFEPAY_DIRDEB_MIN_AMOUNT', $min_amount);
            Configuration::updateValue('MULTISAFEPAY_DIRDEB_MAX_AMOUNT', $max_amount);
            Configuration::updateValue('MULTISAFEPAY_DIRDEB_DIRECT', $direct);
        }

        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];

        $min_amount = Configuration::get('MULTISAFEPAY_DIRDEB_MIN_AMOUNT');
        $max_amount = Configuration::get('MULTISAFEPAY_DIRDEB_MAX_AMOUNT');
        $direct = Configuration::get('MULTISAFEPAY_DIRDEB_DIRECT');

        $helper->fields_value['MULTISAFEPAY_DIRDEB_MIN_AMOUNT'] = $min_amount;
        $helper->fields_value['MULTISAFEPAY_DIRDEB_MAX_AMOUNT'] = $max_amount;
        $helper->fields_value['MULTISAFEPAY_DIRDEB_DIRECT'] = $direct;

        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('General settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Minimal order amount for Direct debit'),
                    'name' => 'MULTISAFEPAY_DIRDEB_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for Direct debit'),
                    'name' => 'MULTISAFEPAY_DIRDEB_MAX_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Use modal for direct checkout'),
                    'name' => 'MULTISAFEPAY_DIRDEB_DIRECT',
                    'required' => true,
                    'values' => [
                        [
                            'id' => 'direct',
                            'value' => true,
                            'label' => $this->l('Use modal'),
                        ],
                        [
                            'id' => 'redirect',
                            'value' => false,
                            'label' => $this->l('Do not use modal'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        return $helper->generateForm($fields_form);
    }
}
