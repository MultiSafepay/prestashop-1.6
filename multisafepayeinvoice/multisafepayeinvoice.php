<?php
/**
 * MultiSafepay Payment Module
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

class MultisafepayEinvoice extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepayeinvoice';
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

        $this->gateway = 'EINVOICE';
        $this->displayName = $this->l('E-Invoice');
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
        Configuration::updateValue('MULTISAFEPAY_EINVOICE_API_KEY', '');
        Configuration::updateValue('MULTISAFEPAY_EINVOICE_SANDBOX', '');
        Configuration::updateValue('MULTISAFEPAY_EINVOICE_IP_FILTER', false);
        Configuration::updateValue('MULTISAFEPAY_EINVOICE_IP_ADDRESSES', '');
        Configuration::updateValue('MULTISAFEPAY_EINVOICE_DIRECT', true);

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_API_KEY');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_SANDBOX');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_IP_FILTER');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_IP_ADDRESSES');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_DIRECT');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_EINVOICE_MAX_AMOUNT');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $address = explode(';', Configuration::get('MULTISAFEPAY_EINVOICE_IP_ADDRESSES'));
        if (Configuration::get('MULTISAFEPAY_EINVOICE_IP_FILTER') && !in_array($_SERVER['REMOTE_ADDR'], $address)) {
            return false;
        }

        $min_amount = (int) Configuration::get('MULTISAFEPAY_EINVOICE_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_EINVOICE_MAX_AMOUNT');

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
            'direct' => true,
            'fields' => [
                [
                    'type' => 'date',
                    'name' => 'birthday',
                    'label' => 'Date of birth',
                    'required' => true,
                ],
                [
                    'type' => 'tel',
                    'name' => 'phone',
                    'label' => 'Phonenumber',
                    'required' => true,
                    'placeholder' => '0612345678',
                ],
                [
                    'type' => 'text',
                    'name' => 'bankaccount',
                    'label' => 'Bank Account',
                    'required' => true,
                    'placeholder' => 'NL87ABNA0000000001',
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
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_API_KEY', Tools::getValue('MULTISAFEPAY_EINVOICE_API_KEY'));
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_SANDBOX', Tools::getValue('MULTISAFEPAY_EINVOICE_SANDBOX'));
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_IP_FILTER', Tools::getValue('MULTISAFEPAY_EINVOICE_IP_FILTER'));
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_IP_ADDRESSES', Tools::getValue('MULTISAFEPAY_EINVOICE_IP_ADDRESSES'));
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_MIN_AMOUNT', Tools::getValue('MULTISAFEPAY_EINVOICE_MIN_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_EINVOICE_MAX_AMOUNT', Tools::getValue('MULTISAFEPAY_EINVOICE_MAX_AMOUNT'));
        }

        $CheckConnection = new CheckConnection('', '');
        $check = $CheckConnection->checkConnection(Tools::getValue('MULTISAFEPAY_EINVOICE_API_KEY'),
            Tools::getValue('MULTISAFEPAY_EINVOICE_SANDBOX'));

        if ($check) {
            $output = $this->displayError($check);
        } else {
            $output = $this->displayConfirmation($this->l('Settings updated'));
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

        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Account Settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'MULTISAFEPAY_EINVOICE_API_KEY',
                    'size' => 20,
                    'required' => false,
                    'hint' => $this->l('The API-Key from the corresponding website in your MultiSafepay account. Leave empty to use the API-Key configured in the default MultiSafepay gateway'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Test account'),
                    'name' => 'MULTISAFEPAY_EINVOICE_SANDBOX',
                    'class' => 't',
                    'is_bool' => true,
                    'required' => false,
                    'hint' => $this->l('Use Live-account the API-Key is from your MultiSafepay LIVE-account.<br/>Use Test -account if the API-Key is from your MultiSafepay TEST-account.'),
                    'values' => [
                        [
                            'id' => 'test',
                            'value' => true,
                            'label' => $this->l('Test account'),
                        ],
                        [
                            'id' => 'prod',
                            'value' => false,
                            'label' => $this->l('Live account'),
                        ],
                    ],
                ],
            ],
        ];

        $fields_form[1]['form'] = [
            'legend' => [
                'title' => $this->l('General Settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Enable IP-Filter'),
                    'name' => 'MULTISAFEPAY_EINVOICE_IP_FILTER',
                    'class' => 't',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'yes',
                            'value' => true,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'no',
                            'value' => false,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'required' => false,
                    'hint' => $this->l('If enabled E-Invoice is only available for the given IP numbers'),
                ],

                [
                    'type' => 'textarea',
                    'label' => $this->l('IP addresses'),
                    'name' => 'MULTISAFEPAY_EINVOICE_IP_ADDRESSES',
                    'size' => 20,
                    'required' => false,
                    'hint' => $this->l('IP-Addresses seperate by semicolumn'),
                ],

                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Minimal order amount for E-Invoice'),
                    'name' => 'MULTISAFEPAY_EINVOICE_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for E-Invoice'),
                    'name' => 'MULTISAFEPAY_EINVOICE_MAX_AMOUNT',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_EINVOICE_API_KEY'] = Configuration::get('MULTISAFEPAY_EINVOICE_API_KEY');
        $helper->fields_value['MULTISAFEPAY_EINVOICE_SANDBOX'] = Configuration::get('MULTISAFEPAY_EINVOICE_SANDBOX');
        $helper->fields_value['MULTISAFEPAY_EINVOICE_IP_FILTER'] = Configuration::get('MULTISAFEPAY_EINVOICE_IP_FILTER');
        $helper->fields_value['MULTISAFEPAY_EINVOICE_IP_ADDRESSES'] = Configuration::get('MULTISAFEPAY_EINVOICE_IP_ADDRESSES');
        $helper->fields_value['MULTISAFEPAY_EINVOICE_MIN_AMOUNT'] = Configuration::get('MULTISAFEPAY_EINVOICE_MIN_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_EINVOICE_MAX_AMOUNT'] = Configuration::get('MULTISAFEPAY_EINVOICE_MAX_AMOUNT');

        return $output . $helper->generateForm($fields_form);
    }
}
