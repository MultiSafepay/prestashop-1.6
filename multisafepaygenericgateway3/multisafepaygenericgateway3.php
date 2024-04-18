<?php
/**
 *  MultiSafepay Payment Module
 *
 * @author    MultiSafepay <integration@multisafepay.com>
 * @copyright Copyright (c) 2022 MultiSafepay (https://www.multisafepay.com)
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

class MultiSafepayGenericGateway3 extends PaymentModule
{
    public function __construct()
    {
        if (!Module::isInstalled('multisafepay')) {
            return;
        }
        $this->name = 'multisafepaygenericgateway3';
        $this->tab = 'payments_gateways';
        $this->version = '3.13.0';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];
        $this->dependencies = ['multisafepay'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->gateway = 'GENERICGATEWAY3';
        $this->displayName = $this->l('Generic Gateway 3');
        $this->description = $this->l('This module allows you to accept payments by MultiSafepay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');
        $this->gatewayTitle = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE') ?: $this->displayName;

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

        Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE', '');
        Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE', '');

        return true;
    }

    public function uninstall()
    {
        $this->deleteGenericGatewayLogo();

        Configuration::deleteByName('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE');
        Configuration::deleteByName('MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT');
        Configuration::deleteByName('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE');

        return parent::uninstall();
    }

    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $min_amount = (int) Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT');
        $max_amount = (int) Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT');

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        if (($max_amount > 0 && $total > $max_amount) || ($min_amount >= 0 && $total < $min_amount) && ($min_amount != $max_amount)) {
            return;
        }

        $gateway_title = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE');
        $gateway_code = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE');

        $this->context->smarty->clearAssign(MultiSafepay::SMARTY_VARIABLES_TO_UNASSIGN);

        $this->context->smarty->assign([
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'main_path_ssl' => _PS_ROOT_DIR_,
            'moduleLink' => $this->name,
            'gateway' => $gateway_code,
            'name' => $gateway_title,
            'fee' => $this->fee,
            'useTokenization' => false,
            'direct' => false,
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/multisafepay.css', 'all');
        $this->context->smarty->assign(['Multisafepay_module_dir' => _MODULE_DIR_ . $this->name]);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            if (isset($_FILES['MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO'])) {
                if ($error = ImageManager::validateUpload($_FILES['MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO'], 4000000)) {
                    return $error;
                }

                if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO']['tmp_name'], $tmpName)) {
                    return false;
                }

                $_FILES['MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO']['tmp_name'] = $tmpName;

                if (!file_exists(_PS_IMG_DIR_ . '/multisafepay/')) {
                    mkdir(_PS_IMG_DIR_ . '/multisafepay', 0777, true);
                }

                $ext = $this->getFileExtension($_FILES['MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO']['name']);

                if (!ImageManager::resize($tmpName, _PS_IMG_DIR_ . '/multisafepay/' . 'multisafepaygenericgateway3-logo.png.')) {
                    return Tools::displayError('An error occurred while uploading image.');
                }

                unlink($tmpName);

                $imageUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/img/multisafepay/multisafepaygenericgateway3-logo.png';

                if (!isset($_SERVER['HTTPS'])) {
                    $imageUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/img/multisafepay/multisafepaygenericgateway3-logo.png';
                }

                if (Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO', serialize($imageUrl))) {
                    return $this->displayConfirmation($this->l('The settings have been updated.'));
                }

                return Tools::displayError('An error occurred while uploading image.');
            }
        }

        $queries = [];
        parse_str($_SERVER['QUERY_STRING'], $queries);

        if (isset($queries['deleteLogo']) && $queries['deleteLogo']) {
            $this->deleteGenericGatewayLogo();
        }

        return;
    }

    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    public function renderForm()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE', Tools::getValue('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE'));
            Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT', Tools::getValue('MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT', Tools::getValue('MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT'));
            Configuration::updateValue('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE', Tools::getValue('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE'));
        }

        $image = unserialize(Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO'));

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

        $files = [];
        if (!empty(Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO'))) {
            $files = [
                0 => [
                    'type' => HelperUploader::TYPE_IMAGE,
                    'image' => '<img style="margin-bottom: 1em;" src="' . $image . '"/>',
                    'delete_url' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&deleteLogo=1',
                ],
            ];
        }

        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('General settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Gateway Title'),
                    'name' => 'MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE',
                    'class' => 'fixed-width-lg',
                    'required' => true,
                    'hint' => $this->l('Accepts the gateway ID, you can find them on https://docs-api.multisafepay.com/reference/gateway-ids'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Gateway ID'),
                    'name' => 'MULTISAFEPAY_GENERIC_GATEWAY_3_CODE',
                    'class' => 'fixed-width-lg',
                    'required' => true,
                    'hint' => $this->l('Accepts the gateway ID, you can find them on https://docs-api.multisafepay.com/reference/gateway-ids'),
                ],
                [
                    'type' => 'file',
                    'id' => 'genericgateway1logo',
                    'name' => 'MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO',
                    'url' => $image,
                    'ajax' => false,
                    'files' => $files,
                    'max_files' => 1,
                    'label' => 'Gateway Logo',
                    'required' => false,
                    'hint' => $this->l('This allows you to upload a logo for the payment method, that will be visible in the checkout. Recommended size 70x40px'),
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Minimal order amount for this Generic Gateway'),
                    'name' => 'MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'prefix' => $this->context->currency->sign,
                    'label' => $this->l('Maximum order amount for this Generic Gateway'),
                    'name' => 'MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT',
                    'required' => false,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_GENERIC_GATEWAY_3_CODE'] = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_CODE');
        $helper->fields_value['MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT'] = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_MIN_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT'] = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_MAX_AMOUNT');
        $helper->fields_value['MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE'] = Configuration::get('MULTISAFEPAY_GENERIC_GATEWAY_3_TITLE');

        return $helper->generateForm($fields_form);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getFileExtension($fileName)
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    public function deleteGenericGatewayLogo()
    {
        Configuration::deleteByName('MULTISAFEPAY_GENERIC_GATEWAY_3_LOGO');

        $checkoutLogo = glob(_PS_IMG_DIR_ . '/multisafepay/multisafepaygenericgateway3-logo.*');

        chmod($checkoutLogo[0], 0644);

        unlink($checkoutLogo[0]);

        return $this->displayConfirmation($this->l('Succesfully deleted image.'));
    }
}
