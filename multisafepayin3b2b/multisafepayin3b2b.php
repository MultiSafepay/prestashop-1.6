<?php
/**
 * MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com.com>
 * @copyright Copyright (c) 2020 MultiSafepay (https://www.multisafepay.com)
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

class Multisafepayin3b2b extends PaymentModule
{
    public $fee;
    public $gatewayTitle;

    /**
     * Multisafepay In3 constructor.
     */
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepayin3b2b';
        $this->tab = 'payments_gateways';
        $this->version = '3.13.0';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->dependencies = ['multisafepay'];
        $this->bootstrap = true;
        parent::__construct();

        $this->gateway = 'IN3B2B';
        $this->displayName = $this->l('in3: Betaal in 3 delen (0% rente)');
        $this->description = $this->l('Betaal vandaag 1/3 via iDEAL. De tweede en derde termijn betaal je binnen 30 en 60 dagen. Zonder rente.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');

        if (Module::isInstalled('bvkpaymentfees') && Module::isInstalled('multisafepay')) {
            $this->payment_fee = new PaymentFee();
            $this->fee = $this->payment_fee->getFee($this->name);
        }
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('paymentTop') ||
            !$this->registerHook('payment')) {
            return false;
        }
        Configuration::updateValue('MULTISAFEPAY_IN3B2B_MIN_AMOUNT', '150');
        Configuration::updateValue('MULTISAFEPAY_IN3B2B_MAX_AMOUNT', '3000');
        Configuration::updateValue('MULTISAFEPAY_IN3B2B_USE_COMPONENT', true);

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        Configuration::deleteByName('MULTISAFEPAY_IN3B2B_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_IN3B2B_MAX_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_IN3B2B_USE_COMPONENT');

        return parent::uninstall();
    }

    /**
     * @return bool|void
     */
    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $minAmount = (int) Configuration::get('MULTISAFEPAY_IN3B2B_MIN_AMOUNT');
        $maxAmount = (int) Configuration::get('MULTISAFEPAY_IN3B2B_MAX_AMOUNT');
        $useComponent = Configuration::get('MULTISAFEPAY_IN3B2B_USE_COMPONENT');

        if ($this->context->api_access === '0') {
            $useComponent = false;
        }

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        if (($total < $minAmount || $total > $maxAmount) && ($minAmount != $maxAmount)) {
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
            'isComponent' => $useComponent,
            'direct' => $useComponent,
            'useTokenization' => false,
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/multisafepay.css', 'all');
        $this->context->smarty->assign(['Multisafepay_module_dir' => _MODULE_DIR_ . $this->name]);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MULTISAFEPAY_IN3B2B_MIN_AMOUNT', Tools::getValue('MULTISAFEPAY_IN3B2B_MIN_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_IN3B2B_MAX_AMOUNT', Tools::getValue('MULTISAFEPAY_IN3B2B_MAX_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_IN3B2B_USE_COMPONENT', Tools::getValue('MULTISAFEPAY_IN3B2B_USE_COMPONENT'));
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

        $helper->fields_value['MULTISAFEPAY_IN3B2B_MIN_AMOUNT'] = Configuration::get('MULTISAFEPAY_IN3B2B_MIN_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_IN3B2B_MAX_AMOUNT'] = Configuration::get('MULTISAFEPAY_IN3B2B_MAX_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_IN3B2B_USE_COMPONENT'] = Configuration::get('MULTISAFEPAY_IN3B2B_USE_COMPONENT');

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
                    'label' => $this->l('Minimal order amount for in3'),
                    'name' => 'MULTISAFEPAY_IN3B2B_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for in3'),
                    'name' => 'MULTISAFEPAY_IN3B2B_MAX_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Use modal for direct checkout'),
                    'name' => 'MULTISAFEPAY_IN3B2B_USE_COMPONENT',
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
