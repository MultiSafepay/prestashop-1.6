<?php
/**
 * MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com>
 * @copyright Copyright (c) MultiSafepay (http://www.multisafepay.com)
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

class MultiSafepayMyBank extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepaymybank';
        $this->tab = 'payments_gateways';
        $this->version = '3.10.2';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->dependencies = ['multisafepay'];
        $this->bootstrap = true;
        parent::__construct();

        $this->gateway = 'MYBANK';
        $this->displayName = $this->l('MyBank');
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

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        Configuration::deleteByName('MULTISAFEPAY_MYBANK_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_MYBANK_MAX_AMOUNT');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }
        $min_amount = (int) Configuration::get('MULTISAFEPAY_MYBANK_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_MYBANK_MAX_AMOUNT');

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        if (($max_amount > 0 && $total > $max_amount) || ($min_amount >= 0 && $total < $min_amount) && ($min_amount != $max_amount)) {
            return false;
        }

        $msp = new MultiSafepayClient();
        $msp->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $msp->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));

        try {
            $issuers = $msp->issuers->get('issuers', 'mybank');
        } catch (Exception $e) {
            $msg = $this->l('Error:') . htmlspecialchars($e->getMessage());
            echo $msg;
            PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');
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
                    'type' => 'issuers',
                    'name' => 'issuers',
                    'label' => 'Please select your issuer',
                    'required' => true,
                    'options' => $issuers,
                    'select2' => true,
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
            Configuration::updateValue('MULTISAFEPAY_MYBANK_MIN_AMOUNT', Tools::getValue('MULTISAFEPAY_MYBANK_MIN_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_MYBANK_MAX_AMOUNT', Tools::getValue('MULTISAFEPAY_MYBANK_MAX_AMOUNT'));
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
                'title' => $this->l('General Settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Minimal order amount for MyBank'),
                    'name' => 'MULTISAFEPAY_MYBANK_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for MyBank'),
                    'name' => 'MULTISAFEPAY_MYBANK_MAX_AMOUNT',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_MYBANK_MIN_AMOUNT'] = Configuration::get('MULTISAFEPAY_MYBANK_MIN_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_MYBANK_MAX_AMOUNT'] = Configuration::get('MULTISAFEPAY_MYBANK_MAX_AMOUNT');

        return $helper->generateForm($fields_form);
    }
}
