<?php
/**
 * MultiSafepay Payment Module
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

class MultisafepayFco extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepayfco';
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

        $this->gateway = 'FCO';
        $this->displayName = $this->l('FastCheckout');
        $this->description = $this->l('This module allows you to accept payments by MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
    }

    public function install()
    {
        /* Install and register on hook */
        if (!parent::install() ||
            !$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('shoppingCartExtra') ||
            !$this->registerHook('productFooter') ||
            !$this->registerHook('backOfficeHeader')) {
            return false;
        }

        /* Set configuration */

//        Configuration::updateValue('MULTISAFEPAY_FCO_NAME', 'MultiSafepay');
        Configuration::updateValue('MULTISAFEPAY_FCO_API_KEY', '');
        Configuration::updateValue('MULTISAFEPAY_FCO_SANDBOX', '');
//        Configuration::updateValue('MULTISAFEPAY_FCO_CART_BUTTON', true);
//        Configuration::updateValue('MULTISAFEPAY_FCO_ORDER_EMAIL', '');

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        Configuration::deleteByName('MULTISAFEPAY_FCO_API_KEY');
        Configuration::deleteByName('MULTISAFEPAY_FCO_SANDBOX');

        // Backwards compitable with older version of plug-in
        Configuration::deleteByName('MULTISAFEPAY_FCO_PRODUCT_BUY');
        Configuration::deleteByName('MULTISAFEPAY_FCO_CART_BUTTON');
        Configuration::deleteByName('MULTISAFEPAY_FCO_ORDER_EMAIL');
        Configuration::deleteByName('MULTISAFEPAY_FCO_NAME');
        Configuration::deleteByName('MULTISAFEPAY_FCO_ACCOUNT_ID');
        Configuration::deleteByName('MULTISAFEPAY_FCO_SECURE_CODE');
        Configuration::deleteByName('MULTISAFEPAY_FCO_SITE_ID');
        Configuration::deleteByName('MULTISAFEPAY_FCO_TAX_SHIP', '');
        Configuration::deleteByName('MULTISAFEPAY_FCO_DEFAULT_RATE', '');

        return parent::uninstall();
    }

    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/multisafepay.css', 'all');
        $this->context->smarty->assign(['Multisafepay_module_dir' => _MODULE_DIR_ . $this->name]);
    }

    public function hookShoppingCartExtra()
    {
        // todo add form to fco
        $language = Language::getIsoById($this->context->cart->id_lang);

        if ($language == '') {
            $language = 'global';
        }

        $this->context->smarty->assign(['img' => $this->_path . 'logo.png']);

        return $this->display(__FILE__, '/views/templates/hook/fco_shortcut_form.tpl');
    }

    public function hookProductFooter()
    {
        // todo add form to fco
        $language = Language::getIsoById($this->context->cart->id_lang);

        if ($language == '') {
            $language = 'global';
        }

        $this->context->smarty->assign(['img' => $this->_path . '/logo.png']);

        return false;
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MULTISAFEPAY_FCO_API_KEY', Tools::getValue('MULTISAFEPAY_FCO_API_KEY'));
            Configuration::updateValue('MULTISAFEPAY_FCO_SANDBOX', Tools::getValue('MULTISAFEPAY_FCO_SANDBOX'));
//          Configuration::updateValue('MULTISAFEPAY_FCO_PRODUCT_BUY', Tools::getValue('MULTISAFEPAY_FCO_PRODUCT_BUY'));
//          Configuration::updateValue('MULTISAFEPAY_FCO_NAME', Tools::getValue('MULTISAFEPAY_FCO_NAME'));
//          Configuration::updateValue('MULTISAFEPAY_FCO_CART_BUTTON', Tools::getValue('MULTISAFEPAY_FCO_CART_BUTTON'));
//          Configuration::updateValue('MULTISAFEPAY_FCO_ORDER_EMAIL', Tools::getValue('MULTISAFEPAY_FCO_ORDER_EMAIL'));
        }

        $CheckConnection = new CheckConnection('', '');
        $check = $CheckConnection->checkConnection(Tools::getValue('MULTISAFEPAY_FCO_API_KEY'),
                                                    Tools::getValue('MULTISAFEPAY_FCO_SANDBOX'));

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
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
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
                    'name' => 'MULTISAFEPAY_FCO_API_KEY',
                    'size' => 20,
                    'required' => false,
                    'hint' => $this->l('The API-Key from the corresponding website in your MultiSafepay account. Leave empty to use the API-Key configured in the default MultiSafepay gateway'),
                ],

                [
                    'type' => 'switch',
                    'label' => $this->l('Test account'),
                    'name' => 'MULTISAFEPAY_FCO_SANDBOX',
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

/*        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('General settings'),
                'image' => '../img/admin/payment.gif'
            ),
            'input' => array(
                array(
                    'type'          => 'switch',
                    'label'         => $this->l('FastCheckout cart button'),
                    'name'          => 'MULTISAFEPAY_FCO_CART_BUTTON',
                    'class'         => 't',
                    'is_bool'       =>  true,
                    'values'        => array(
                                          array(
                                              'id' => 'enabled',
                                              'value' => true,
                                              'label' => $this->l('Enabled')
                                          ),
                                          array(
                                              'id' => 'disabled',
                                              'value' => false,
                                              'label' => $this->l('Disabled')
                                          )
                                      ),
                      'required' => false
                  ),

                array(
                    'type'          =>  'select',
                    'label'         =>  $this->l('When send order confirmation email?'),
                    'name'          => 'MULTISAFEPAY_FCO_ORDER_EMAIL',
                    'required'      =>  false,
                    'hint'          =>  $this->l('At what moment should the orders be created?'),
                    'options'       =>  array(
                                            'query' => array(   array ( 'id'   => 'paid',        'name' => $this->l('When order has been paid in full')),
                                                                array ( 'id'   => 'always',      'name' => $this->l('On first status update'))
                                                            ),
                                            'id'    => 'id',
                                            'name'  => 'name'
                                        )
                )

            ),
*/

            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

//        $helper->fields_value['MULTISAFEPAY_FCO_NAME']          = Configuration::get('MULTISAFEPAY_FCO_NAME');
        $helper->fields_value['MULTISAFEPAY_FCO_API_KEY'] = Configuration::get('MULTISAFEPAY_FCO_API_KEY');
        $helper->fields_value['MULTISAFEPAY_FCO_SANDBOX'] = Configuration::get('MULTISAFEPAY_FCO_SANDBOX');
//        $helper->fields_value['MULTISAFEPAY_FCO_CART_BUTTON']   = Configuration::get('MULTISAFEPAY_FCO_CART_BUTTON');
//        $helper->fields_value['MULTISAFEPAY_FCO_ORDER_EMAIL']   = Configuration::get('MULTISAFEPAY_FCO_ORDER_EMAIL');

        return $output . $helper->generateForm($fields_form);
    }
}
