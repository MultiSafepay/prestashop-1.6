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

class MultiSafepayAmazonPay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'multisafepayamazonpay';
        $this->tab = 'payments_gateways';
        $this->version = '3.10.4';
        $this->author = 'MultiSafepay';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];
        $this->dependencies = ['multisafepay'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        parent::__construct();

        $this->gateway = 'AMAZONBTN';
        $this->displayName = $this->l('Amazon Pay');
        $this->description = $this->l('This module allows you to accept Amazon Pay payments through MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');

        if (Module::isInstalled('bvkpaymentfees') && Module::isInstalled('multisafepay')) {
            $this->payment_fee = new PaymentFee();
            $this->fee = $this->payment_fee->getFee($this->name);
        }
    }

    /**
     * @return bool
     *
     * @throws HTMLPurifier_Exception
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('backOfficeHeader') || !$this->registerHook('payment')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT');

        return parent::uninstall();
    }

    /**
     * @return false|string|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }

        if (!$this->active) {
            return;
        }

        $min_amount = (int) Configuration::get('MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT');

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
            'direct' => false,
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * @return void
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/multisafepay.css', 'all');
        $this->context->smarty->assign(['Multisafepay_module_dir' => _MODULE_DIR_ . $this->name]);
    }

    /**
     * @throws HTMLPurifier_Exception
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue(
                'MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT',
                Tools::getValue('MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT')
            );
            Configuration::updateValue(
                'MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT',
                Tools::getValue('MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT')
            );

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderForm();
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
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
                'desc' => $this->l('Back to list'),
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
        ];

        // Setting fields
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
                    'label' => $this->l('Minimal order amount for Amazon Pay'),
                    'name' => 'MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for Amazon Pay'),
                    'name' => 'MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT'] = Configuration::get('MULTISAFEPAY_AMAZONPAY_MIN_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT'] = Configuration::get('MULTISAFEPAY_AMAZONPAY_MAX_AMOUNT');

        return $helper->generateForm($fields_form);
    }
}
