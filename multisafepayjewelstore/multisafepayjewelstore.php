<?php
/**
 *  MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

class Multisafepayjewelstore extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepayjewelstore';
        $this->tab = 'payments_gateways';
        $this->version = '3.13.1';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];
        $this->dependencies = ['multisafepay'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->gateway = 'JEWELSTORE';
        $this->displayName = $this->l('Jewelstore giftcard');
        $this->description = $this->l('This module allows you to accept payments by MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('payment')) {
            return false;
        }
        Configuration::updateValue('MULTISAFEPAY_JEWELSTORE_API_KEY', '');
        Configuration::updateValue('MULTISAFEPAY_JEWELSTORE_SANDBOX', '');

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_JEWELSTORE_API_KEY');
        Configuration::deleteByName('MULTISAFEPAY_JEWELSTORE_SANDBOX');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $this->context->smarty->clearAssign(MultiSafepay::SMARTY_VARIABLES_TO_UNASSIGN);

        $this->context->smarty->assign([
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'main_path_ssl' => _PS_ROOT_DIR_,
            'moduleLink' => $this->name,
            'gateway' => $this->gateway,
            'name' => $this->displayName,
            'direct' => false,
            'useTokenization' => false,
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
            Configuration::updateValue('MULTISAFEPAY_JEWELSTORE_API_KEY', Tools::getValue('MULTISAFEPAY_JEWELSTORE_API_KEY'));
            Configuration::updateValue('MULTISAFEPAY_JEWELSTORE_SANDBOX', Tools::getValue('MULTISAFEPAY_JEWELSTORE_SANDBOX'));
        }

        $CheckConnection = new CheckConnection('', '');
        $check = $CheckConnection->checkConnection(Tools::getValue('MULTISAFEPAY_JEWELSTORE_API_KEY'),
                                                    Tools::getValue('MULTISAFEPAY_JEWELSTORE_SANDBOX'));

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
                'title' => $this->l('Account settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'MULTISAFEPAY_JEWELSTORE_API_KEY',
                    'size' => 20,
                    'required' => false,
                    'hint' => $this->l('The API-Key from the corresponding website in your MultiSafepay account. Leave empty to use the API-Key configured in the default MultiSafepay gateway'),
                ],

                [
                    'type' => 'switch',
                    'label' => $this->l('Test account'),
                    'name' => 'MULTISAFEPAY_JEWELSTORE_SANDBOX',
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
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_JEWELSTORE_API_KEY'] = Configuration::get('MULTISAFEPAY_JEWELSTORE_API_KEY');
        $helper->fields_value['MULTISAFEPAY_JEWELSTORE_SANDBOX'] = Configuration::get('MULTISAFEPAY_JEWELSTORE_SANDBOX');

        return $output . $helper->generateForm($fields_form);
    }
}
