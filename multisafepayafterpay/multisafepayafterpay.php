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

class MultisafepayAfterpay extends PaymentModule
{
    public $fee;
    public $gatewayTitle;

    const DEFAULT_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default';
    const INVOICE_ADDRESS_DE_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_en/default';
    const INVOICE_ADDRESS_DE_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_de/default';
    const INVOICE_ADDRESS_AT_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_en/default';
    const INVOICE_ADDRESS_AT_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_de/default';
    const INVOICE_ADDRESS_CH_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_en/default';
    const INVOICE_ADDRESS_CH_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_de/default';
    const INVOICE_ADDRESS_CH_LOCALE_FR_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_fr/default';
    const INVOICE_ADDRESS_NL_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default';
    const INVOICE_ADDRESS_NL_LOCALE_NL_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_nl/default';
    const INVOICE_ADDRESS_BE_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_en/default';
    const INVOICE_ADDRESS_BE_LOCALE_NL_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_nl/default';
    const INVOICE_ADDRESS_BE_LOCALE_FR_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_fr/default';

    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }

        $this->name = 'multisafepayafterpay';
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

        $this->gateway = 'AFTERPAY';
        $this->displayName = $this->l('Riverty');
        $this->description = $this->l('This module allows you to accept payments by MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');

        if (Module::isInstalled('bvkpaymentfees') && Module::isInstalled('multisafepay')) {
            $this->payment_fee = new PaymentFee();
            $this->fee = $this->payment_fee->getFee($this->name);
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('backOfficeHeader') || !$this->registerHook('payment')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_AFTERPAY_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_AFTERPAY_MAX_AMOUNT');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $min_amount = (int) Configuration::get('MULTISAFEPAY_AFTERPAY_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_AFTERPAY_MAX_AMOUNT');

        $direct = true;
        if ($this->context->api_access === '0') {
            $direct = false;
        }

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

        if (($max_amount > 0 && $total > $max_amount) || ($min_amount >= 0 && $total < $min_amount) && ($min_amount != $max_amount)) {
            return false;
        }

        $addressDelivery = new Address($this->context->cart->id_address_delivery);
        $customer = new Customer($this->context->cart->id_customer);

        $this->context->smarty->clearAssign(MultiSafepay::SMARTY_VARIABLES_TO_UNASSIGN);

        $this->context->smarty->assign([
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'main_path_ssl' => _PS_ROOT_DIR_,
            'moduleLink' => $this->name,
            'gateway' => $this->gateway,
            'name' => $this->displayName,
            'fee' => $this->fee,
            'direct' => $direct,
            'useTokenization' => false,
            'fields' => [
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'Email',
                    'required' => true,
                    'placeholder' => '',
                    'value' => $customer->email,
                ],
                [
                    'type' => 'tel',
                    'name' => 'phone',
                    'label' => 'Phone',
                    'required' => true,
                    'placeholder' => '0612345678',
                    'value' => $addressDelivery->phone ?: $addressDelivery->phone_mobile,
                ],
                [
                    'type' => 'date',
                    'name' => 'birthday',
                    'label' => 'Birthday',
                    'required' => true,
                    'value' => $customer->birthday,
                ],
                [
                    'type' => 'select',
                    'name' => 'gender',
                    'label' => $this->l('Salutation'),
                    'required' => true,
                    'options' => [
                        [
                            'name' => $this->l('Mr'),
                            'value' => 'mr',
                        ],
                        [
                            'name' => $this->l('Ms'),
                            'value' => 'ms',
                        ],
                        [
                            'name' => $this->l('Miss'),
                            'value' => 'miss',
                        ],
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'terms-and-conditions',
                    'label' => 'I have read and agree to the Riverty payment terms',
                    'link' => $this->getTermsAndConditionsUrl($addressDelivery),
                    'required' => true,
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
            $min_amount = Tools::getValue('MULTISAFEPAY_AFTERPAY_MIN_AMOUNT');
            $max_amount = Tools::getValue('MULTISAFEPAY_AFTERPAY_MAX_AMOUNT');

            Configuration::updateValue('MULTISAFEPAY_AFTERPAY_MIN_AMOUNT', $min_amount);
            Configuration::updateValue('MULTISAFEPAY_AFTERPAY_MAX_AMOUNT', $max_amount);
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

        $min_amount = Configuration::get('MULTISAFEPAY_AFTERPAY_MIN_AMOUNT');
        $max_amount = Configuration::get('MULTISAFEPAY_AFTERPAY_MAX_AMOUNT');

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
                    'label' => $this->l('Minimal order amount for Riverty'),
                    'name' => 'MULTISAFEPAY_AFTERPAY_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for Riverty'),
                    'name' => 'MULTISAFEPAY_AFTERPAY_MAX_AMOUNT',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_AFTERPAY_MIN_AMOUNT'] = $min_amount;
        $helper->fields_value['MULTISAFEPAY_AFTERPAY_MAX_AMOUNT'] = $max_amount;

        return $helper->generateForm($fields_form);
    }

    /**
     * @param $addressDelivery
     *
     * @return string|void
     */
    private function getTermsAndConditionsUrl($addressDelivery)
    {
        $country = new Country();
        $country_iso = $country::getIsoById($addressDelivery->id_country);

        $language_code = strtolower($this->context->language->language_code);

        if ($country_iso === 'AT') {
            if (strpos($language_code, 'de') !== false) {
                return self::INVOICE_ADDRESS_AT_LOCALE_DE_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_AT_LOCALE_EN_TERMS_URL;
        }

        if ($country_iso === 'BE') {
            if (strpos($language_code, 'nl') !== false) {
                return self::INVOICE_ADDRESS_BE_LOCALE_NL_TERMS_URL;
            }
            if (strpos($language_code, 'fr') !== false) {
                return self::INVOICE_ADDRESS_BE_LOCALE_FR_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_BE_LOCALE_EN_TERMS_URL;
        }

        if ($country_iso === 'CH') {
            if (strpos($language_code, 'de') !== false) {
                return self::INVOICE_ADDRESS_CH_LOCALE_DE_TERMS_URL;
            }

            if (strpos($language_code, 'fr') !== false) {
                return self::INVOICE_ADDRESS_CH_LOCALE_FR_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_CH_LOCALE_EN_TERMS_URL;
        }

        if ($country_iso === 'DE') {
            if (strpos($language_code, 'de') !== false) {
                return self::INVOICE_ADDRESS_DE_LOCALE_DE_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_DE_LOCALE_EN_TERMS_URL;
        }

        if ($country_iso === 'NL') {
            if (strpos($language_code, 'nl') !== false) {
                return self::INVOICE_ADDRESS_NL_LOCALE_NL_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_NL_LOCALE_EN_TERMS_URL;
        }

        return self::DEFAULT_TERMS_URL;
    }
}
