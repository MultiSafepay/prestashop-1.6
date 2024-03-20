<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/api/Autoloader.php';
require_once _PS_MODULE_DIR_ . 'multisafepay/helpers/Autoloader.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultiSafepay extends PaymentModule
{
    const MULTISAFEPAY_COMPONENT_JS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.js';
    const MULTISAFEPAY_COMPONENT_CSS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.css';
    const SMARTY_VARIABLES_TO_UNASSIGN = ['direct', 'birthday', 'gender', 'phone', 'bankaccount', 'issuers', 'fee', 'isComponent', 'useTokenization', 'multisafepay_tokens'];

    public function __construct()
    {
        $this->name = 'multisafepay';
        $this->tab = 'payments_gateways';
        $this->version = '3.12.0';
        $this->author = 'MultiSafepay';

        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('MultiSafepay');
        $this->description = $this->l('Accept payments by MultiSafepay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!$this->isRegisteredInHook('MspPaymentComponent')) {
            $this->registerHook('MspPaymentComponent');
        }
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayOrderConfirmation')
            || !$this->registerHook('displayPaymentTop')
            || !$this->registerHook('actionOrderStatusPostUpdate')
        ) {
            return false;
        }

        Configuration::updateValue('MULTISAFEPAY_NAME', 'MultiSafepay');
        Configuration::updateValue('MULTISAFEPAY_SANDBOX', true);
        Configuration::updateValue('MULTISAFEPAY_API_KEY', '');
        Configuration::updateValue('MULTISAFEPAY_TIME_ACTIVE', '30');
        Configuration::updateValue('MULTISAFEPAY_TIME_LABEL', 'days');
        Configuration::updateValue('MULTISAFEPAY_DEBUG_MODE', false);
        Configuration::updateValue('MULTISAFEPAY_WHEN_CREATE_ORDER', 'After_Confirmation');
        Configuration::updateValue('MULTISAFEPAY_DISABLE_SHOPPING_CART', false);
        Configuration::updateValue('MULTISAFEPAY_TEMPLATE_ID_VALUE', '');

        $multisafepay_stats = [
            'new_order' => ['new_order', false, '#4169e1', false, '', false, false],
            'initialized' => ['initialized', false, '#ff8c00', false, '', false, false],
            'completed' => ['completed', true, '#32cd32',  true, 'payment', true, true],
            'uncleared' => ['uncleared', false, '#32cd32', false, '', false, false],
            'void' => ['void', true, '#dc143c', false, 'order_canceled', false, false],
            'cancelled' => ['cancelled', true, '#dc143c', false, 'order_canceled', false, false],
            'expired' => ['expired', false, '#dc143c', false, '', false, false],
            'declined' => ['declined', false, '#8f0621', false, '', false, false],
            'shipped' => ['shipped', false, '#8f0621', false,  '', false, false],
            'refunded' => ['refunded', true, '#ec2e15', false, 'refund', false, false],
            'partial_refunded' => ['partial_refunded', true, '#ec2e15', false, 'refund', false, false],
        ];

        foreach ($multisafepay_stats as $status => $value) {
            if (!Configuration::get('MULTISAFEPAY_OS_' . Tools::strtoupper($status))) {
                $order_state = new OrderState();
                $order_state->name = [];
                foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = 'MultiSafepay ' . $value[0];
                }

                $order_state->send_email = $value[1];
                $order_state->color = $value[2];
                $order_state->invoice = $value[3];
                $order_state->template = $value[4];
                $order_state->paid = $value[5];
                $order_state->logable = $value[6];
                $order_state->hidden = false;
                $order_state->delivery = false;

                $order_state->add();
                Configuration::updateValue('MULTISAFEPAY_OS_' . Tools::strtoupper($status), (int) $order_state->id);
            }
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MULTISAFEPAY_NAME');
        Configuration::deleteByName('MULTISAFEPAY_SANDBOX');
        Configuration::deleteByName('MULTISAFEPAY_API_KEY');

        Configuration::deleteByName('MULTISAFEPAY_WHEN_CREATE_ORDER');
        Configuration::deleteByName('MULTISAFEPAY_DISABLE_SHOPPING_CART');
        Configuration::deleteByName('MULTISAFEPAY_TEMPLATE_ID_VALUE');

        Configuration::deleteByName('MULTISAFEPAY_DEBUG_MODE');
        Configuration::deleteByName('MULTISAFEPAY_TIME_ACTIVE');
        Configuration::deleteByName('MULTISAFEPAY_TIME_LABEL');

        // Needed to remove older versions of the plugin.
        Configuration::deleteByName('MULTISAFEPAY_DAYS_ACTIVE');
        Configuration::deleteByName('MULTISAFEPAY_ORDER_CONFIRM_BEFORE');
        Configuration::deleteByName('MULTISAFEPAY_ORDER_CONFIRM_PAID');
        Configuration::deleteByName('MULTISAFEPAY_ACCOUNT_ID');
        Configuration::deleteByName('MULTISAFEPAY_SITE_SECURE_CODE');
        Configuration::deleteByName('MULTISAFEPAY_SITE_ID');
        Configuration::deleteByName('MULTISAFEPAY_SEND_CONFIRMATION');
        Configuration::deleteByName('MULTISAFEPAY_EXTRA_CONFIRM');
        Configuration::deleteByName('MULTISAFEPAY_NURL_MODE');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('MULTISAFEPAY_API_KEY', Tools::getValue('MULTISAFEPAY_API_KEY'));
            Configuration::updateValue('MULTISAFEPAY_SANDBOX', Tools::getValue('MULTISAFEPAY_SANDBOX'));
            Configuration::updateValue('MULTISAFEPAY_WHEN_CREATE_ORDER', Tools::getValue('MULTISAFEPAY_WHEN_CREATE_ORDER'));
            Configuration::updateValue('MULTISAFEPAY_DISABLE_SHOPPING_CART', Tools::getValue('MULTISAFEPAY_DISABLE_SHOPPING_CART'));
            Configuration::updateValue('MULTISAFEPAY_TEMPLATE_ID_VALUE', Tools::getValue('MULTISAFEPAY_TEMPLATE_ID_VALUE'));
            Configuration::updateValue('MULTISAFEPAY_TIME_ACTIVE', Tools::getValue('MULTISAFEPAY_TIME_ACTIVE'));
            Configuration::updateValue('MULTISAFEPAY_TIME_LABEL', Tools::getValue('MULTISAFEPAY_TIME_LABEL'));
            Configuration::updateValue('MULTISAFEPAY_SECONDS_ACTIVE', Tools::getValue('MULTISAFEPAY_SECONDS_ACTIVE'));
            Configuration::updateValue('MULTISAFEPAY_DEBUG_MODE', Tools::getValue('MULTISAFEPAY_DEBUG_MODE'));
            Configuration::updateValue('MULTISAFEPAY_OS_NEW_ORDER', Tools::getValue('MULTISAFEPAY_OS_NEW_ORDER'));
            Configuration::updateValue('MULTISAFEPAY_OS_INITIALIZED', Tools::getValue('MULTISAFEPAY_OS_INITIALIZED'));
            Configuration::updateValue('MULTISAFEPAY_OS_COMPLETED', Tools::getValue('MULTISAFEPAY_OS_COMPLETED'));
            Configuration::updateValue('MULTISAFEPAY_OS_UNCLEARED', Tools::getValue('MULTISAFEPAY_OS_UNCLEARED'));
            Configuration::updateValue('MULTISAFEPAY_OS_CANCELLED', Tools::getValue('MULTISAFEPAY_OS_CANCELLED'));
            Configuration::updateValue('MULTISAFEPAY_OS_VOID', Tools::getValue('MULTISAFEPAY_OS_VOID'));
            Configuration::updateValue('MULTISAFEPAY_OS_EXPIRED', Tools::getValue('MULTISAFEPAY_OS_EXPIRED'));
            Configuration::updateValue('MULTISAFEPAY_OS_DECLINED', Tools::getValue('MULTISAFEPAY_OS_DECLINED'));
            Configuration::updateValue('MULTISAFEPAY_OS_REFUNDED', Tools::getValue('MULTISAFEPAY_OS_REFUNDED'));
            Configuration::updateValue('MULTISAFEPAY_OS_PARTIAL_REFUNDED', Tools::getValue('MULTISAFEPAY_OS_PARTIAL_REFUNDED'));
            Configuration::updateValue('MULTISAFEPAY_OS_SHIPPED', Tools::getValue('MULTISAFEPAY_OS_SHIPPED'));

            $CheckConnection = new CheckConnection('', '');
            $check = $CheckConnection->checkConnection(
                Tools::getValue('MULTISAFEPAY_API_KEY'),
                Tools::getValue('MULTISAFEPAY_SANDBOX')
            );
            if ($check) {
                $output = $this->displayError($check);
            }

            switch (Tools::getValue('MULTISAFEPAY_TIME_LABEL')) {
                case 'days':
                    $seconds_active = Tools::getValue('MULTISAFEPAY_TIME_ACTIVE') * 24 * 60 * 60;
                    break;
                case 'hours':
                    $seconds_active = Tools::getValue('MULTISAFEPAY_TIME_ACTIVE') * 60 * 60;
                    break;
                case 'seconds':
                    $seconds_active = Tools::getValue('MULTISAFEPAY_TIME_ACTIVE');
                    break;
            }

            Configuration::updateValue('MULTISAFEPAY_SECONDS_ACTIVE', $seconds_active);

            if ($output == null) {
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
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
        $order_states_db = OrderState::getOrderStates($this->context->language->id);
        $order_states = [];
        foreach ($order_states_db as $order_state) {
            $order_states[] = [
                'id' => $order_state['id_order_state'],
                'name' => $order_state['name'],
            ];
        }

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
                    'name' => 'MULTISAFEPAY_API_KEY',
                    'size' => 20,
                    'required' => true,
                    'hint' => $this->l('The API-Key from the corresponding website in your MultiSafepay account'),
                ],

                [
                    'type' => 'switch',
                    'label' => $this->l('Test account'),
                    'name' => 'MULTISAFEPAY_SANDBOX',
                    'class' => 't',
                    'is_bool' => true,
                    'required' => true,
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
                    'label' => $this->l('Debug'),
                    'name' => 'MULTISAFEPAY_DEBUG_MODE',
                    'class' => 't',
                    'required' => false,
                    'is_bool' => true,
                    'hint' => $this->l('Use to debug into the MultiSafepay logfile.'),
                    'values' => [
                        [
                            'id' => 'true',
                            'value' => true,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'false',
                            'value' => false,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-sm',
                    'label' => $this->l('Time an order stays active'),
                    'hint' => $this->l('Time an order stays active before the orderstatus is set to expired'),
                    'name' => 'MULTISAFEPAY_TIME_ACTIVE',
                    'required' => false,
                ],
                [
                    'type' => 'select',
                    'name' => 'MULTISAFEPAY_TIME_LABEL',
                    'required' => false,
                    'options' => [
                        'query' => [
                            [
                                'id' => 'days',
                                'name' => $this->l('Days')
                            ],
                            [
                                'id' => 'hours',
                                'name' => $this->l('Hours')
                            ],
                            [
                                'id' => 'seconds',
                                'name' => $this->l('Seconds')
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Moment of order creation'),
                    'name' => 'MULTISAFEPAY_WHEN_CREATE_ORDER',
                    'required' => false,
                    'hint' => $this->l('At what moment should the orders be created?'),
                    'options' => [
                        'query' => [
                            [
                                'id' => 'After_Confirmation',
                                'name' => $this->l('After order is confirmed')
                            ],
                            [
                                'id' => 'After_Payment_Complete',
                                'name' => $this->l('After order is paid in full')
                            ],
                            [
                                'id' => 'After_Payment_Complete_Inc_Banktrans',
                                'name' => $this->l('After order is paid in full or by banktransfer')
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Disable Shopping Cart'),
                    'name' => 'MULTISAFEPAY_DISABLE_SHOPPING_CART',
                    'class' => 't',
                    'required' => false,
                    'is_bool' => true,
                    'hint' => $this->l('Enable this option to hide the cart items on the MultiSafepay payment page, leaving only the total order amount. Note: If is enabled, the payment methods which require shopping cart will not work: Riverty, E-Invoicing, in3, Klarna and Pay After Delivery.'),
                    'values' => [
                        [
                            'id' => 'true',
                            'value' => true,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'false',
                            'value' => false,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'class' => 'fixed-width-lg',
                    'label' => $this->l('Payment Component Template ID'),
                    'hint' => $this->l('If empty, the default one will be used.'),
                    'name' => 'MULTISAFEPAY_TEMPLATE_ID_VALUE',
                    'required' => false,
                ],
            ],
        ];
        $fields_form[2]['form'] = [
            'legend' => [
                'title' => $this->l('Status Settings'),
                'image' => '../img/admin/edit.gif',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('New order'),
                    'name' => 'MULTISAFEPAY_OS_NEW_ORDER',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Initialized'),
                    'name' => 'MULTISAFEPAY_OS_INITIALIZED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Completed'),
                    'name' => 'MULTISAFEPAY_OS_COMPLETED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Uncleared'),
                    'name' => 'MULTISAFEPAY_OS_UNCLEARED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Void'),
                    'name' => 'MULTISAFEPAY_OS_VOID',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Cancelled'),
                    'name' => 'MULTISAFEPAY_OS_CANCELLED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Expired'),
                    'name' => 'MULTISAFEPAY_OS_EXPIRED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Declined'),
                    'name' => 'MULTISAFEPAY_OS_DECLINED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Refunded'),
                    'name' => 'MULTISAFEPAY_OS_REFUNDED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Partial Refunded'),
                    'name' => 'MULTISAFEPAY_OS_PARTIAL_REFUNDED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Shipped'),
                    'name' => 'MULTISAFEPAY_OS_SHIPPED',
                    'required' => false,
                    'options' => [
                        'query' => $order_states,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper->fields_value['MULTISAFEPAY_API_KEY'] = Configuration::get('MULTISAFEPAY_API_KEY');
        $helper->fields_value['MULTISAFEPAY_SANDBOX'] = Configuration::get('MULTISAFEPAY_SANDBOX');
        $helper->fields_value['MULTISAFEPAY_WHEN_CREATE_ORDER'] = Configuration::get('MULTISAFEPAY_WHEN_CREATE_ORDER');
        $helper->fields_value['MULTISAFEPAY_DISABLE_SHOPPING_CART'] = Configuration::get('MULTISAFEPAY_DISABLE_SHOPPING_CART');
        $helper->fields_value['MULTISAFEPAY_TEMPLATE_ID_VALUE'] = Configuration::get('MULTISAFEPAY_TEMPLATE_ID_VALUE');
        $helper->fields_value['MULTISAFEPAY_OS_NEW_ORDER'] = Configuration::get('MULTISAFEPAY_OS_NEW_ORDER');
        $helper->fields_value['MULTISAFEPAY_OS_INITIALIZED'] = Configuration::get('MULTISAFEPAY_OS_INITIALIZED');
        $helper->fields_value['MULTISAFEPAY_OS_COMPLETED'] = Configuration::get('MULTISAFEPAY_OS_COMPLETED');
        $helper->fields_value['MULTISAFEPAY_OS_UNCLEARED'] = Configuration::get('MULTISAFEPAY_OS_UNCLEARED');
        $helper->fields_value['MULTISAFEPAY_OS_VOID'] = Configuration::get('MULTISAFEPAY_OS_VOID');
        $helper->fields_value['MULTISAFEPAY_OS_CANCELLED'] = Configuration::get('MULTISAFEPAY_OS_CANCELLED');
        $helper->fields_value['MULTISAFEPAY_OS_EXPIRED'] = Configuration::get('MULTISAFEPAY_OS_EXPIRED');
        $helper->fields_value['MULTISAFEPAY_OS_DECLINED'] = Configuration::get('MULTISAFEPAY_OS_DECLINED');
        $helper->fields_value['MULTISAFEPAY_OS_REFUNDED'] = Configuration::get('MULTISAFEPAY_OS_REFUNDED');
        $helper->fields_value['MULTISAFEPAY_OS_PARTIAL_REFUNDED'] = Configuration::get('MULTISAFEPAY_OS_PARTIAL_REFUNDED');
        $helper->fields_value['MULTISAFEPAY_OS_SHIPPED'] = Configuration::get('MULTISAFEPAY_OS_SHIPPED');
        $helper->fields_value['MULTISAFEPAY_TIME_ACTIVE'] = Configuration::get('MULTISAFEPAY_TIME_ACTIVE');
        $helper->fields_value['MULTISAFEPAY_TIME_LABEL'] = Configuration::get('MULTISAFEPAY_TIME_LABEL');
        $helper->fields_value['MULTISAFEPAY_DEBUG_MODE'] = Configuration::get('MULTISAFEPAY_DEBUG_MODE');

        return $output . $helper->generateForm($fields_form);
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int) $cart->id_currency);
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookdisplayPaymentTop()
    {
        $this->errors = unserialize($this->context->cookie->msp_error);

        // By default, API access is assumed as accessible
        // and added as string 1 to the cookie
        $this->context->api_access = '1';

        // Connect to servers to check API access
        $checkConnection = new CheckConnection('', '');
        $check = $checkConnection->checkConnection(
            Configuration::get('MULTISAFEPAY_API_KEY'),
            Configuration::get('MULTISAFEPAY_SANDBOX')
        );
        // Otherwise 0 is added to the same cookie
        if ($check) {
            $this->context->api_access = '0';
        }

        // Clear the error cookie
        $this->context->cookie->msp_error = null;

        // Write all the cookies to the browser
        $this->context->cookie->write();

        if (!$this->active || !$this->errors) {
            return;
        }

        $this->context->smarty->assign(
            [
            'errors' => $this->errors,
            ]
        );

        return $this->display(__FILE__, 'errors.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['objOrder'];

        if (stripos($order->module, 'MultiSafepay') === false) {
            return;
        }

        $msp = new MultiSafepayClient();
        $msp->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $msp->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));
        $transactionid = Tools::getValue('id_order');

        // Get the order status
        try {
            $transaction = $msp->orders->get($transactionid, 'orders', [], false);
        } catch (MultiSafepay_API_Exception $e) {
        }

        $this->context->smarty->assign(
            [
            'order' => $order,
            'order_products' => $order->getProducts(),
            ]
        );

        return $this->display(__FILE__, 'order-confirmation.tpl');
    }

    public function hookHeader()
    {
        if (Dispatcher::getInstance()->getController() !== 'order' && Dispatcher::getInstance()->getController() !== 'orderopc') {
            return;
        }
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/multisafepay/views/css/multisafepay.css', 'all');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/multisafepay/views/js/multisafepay.js', 'all');

        // Select2
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/multisafepay/views/css/select2.min.css', 'all');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/multisafepay/views/js/select2.min.js', 'all');

        $this->context->controller->addJS(self::MULTISAFEPAY_COMPONENT_JS_URL);
        $this->context->controller->addCSS(self::MULTISAFEPAY_COMPONENT_CSS_URL, 'all');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/multisafepay/views/js/payment-component.js', 'all');
    }

    private function getLanguageCode($isoCode)
    {
        $locale = Language::getLanguageCodeByIso($isoCode);

        if (Tools::strlen($locale) === 2) {
            return Tools::strtolower($locale) . '_' . Tools::strtoupper($locale);
        }

        $parts = explode('-', (string) $locale);
        $languageCode = Tools::strtolower($parts[0]) . '_' . Tools::strtoupper($parts[1]);

        return $languageCode;
    }

    private function getApiToken()
    {
        $msp = new MultiSafepayClient();
        $msp->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $msp->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));
        try {
            return $msp->apiToken->get();
        } catch (Exception $e) {
            $msg = $this->l('Error:') . htmlspecialchars($e->getMessage());
            PrestaShopLogger::addLog('ApiToken: ' . $msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');

            return '';
        }
    }

    public function hookMspPaymentComponent($params)
    {
        $useTokenization = false;

        $customer = new Customer($this->context->cart->id_customer);
        if (!$customer->is_guest) {
            $useTokenization = isset($params['useTokenization']) && $params['useTokenization'];
        }

        $config = [
            'gateway' => $params['gateway'],
            'debug' => (bool) Configuration::get('MULTISAFEPAY_DEBUG_MODE'),
            'env' => (bool) Configuration::get('MULTISAFEPAY_SANDBOX') ? 'test' : 'live',
            'apiToken' => $this->getApiToken()->api_token,
            'orderData' => [
                'currency' => (new Currency($this->context->cart->id_currency))->iso_code,
                'amount' => ($this->context->cart->getOrderTotal(true, Cart::BOTH) * 100),
                'customer' => [
                    'locale' => $this->getLanguageCode(Language::getIsoById($this->context->cart->id_lang)),
                    'country' => (new Country((new Address((int) $this->context->cart->id_address_invoice))->id_country))->iso_code,
                ],
            ],
            'payment_options' => [
                'template' => [
                    'settings' => [
                        'embed_mode' => true,
                    ],
                    'merge' => true,
                ],
            ],
            'recurring' => [
                'tokens' => $this->getTokens($params['gateway'], $this->context->cart->id_customer),
                'model' => $useTokenization ? 'cardOnFile' : null,
            ],
        ];

        // Payment Component Template ID.
        $templateId = Configuration::get('MULTISAFEPAY_TEMPLATE_ID_VALUE') ? Configuration::get('MULTISAFEPAY_TEMPLATE_ID_VALUE') : '';
        if (!empty($templateId)) {
            $config['orderData']['payment_options']['template_id'] = $templateId;
        }

        $this->context->smarty->assign(
            [
            'config' => $config,
            ]
        );

        return $this->display(__FILE__, 'payment-component.tpl');
    }

    /**
     * Return tokens as an array
     *
     * @param $gateway
     * @param $customerReference
     * @return array
     */
    private function getTokens($gateway, $customerReference)
    {
        $msp = new MultiSafepayClient();
        $msp->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $msp->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));

        try {
            return $msp->tokens->get_by_gateway($gateway, $customerReference);
        } catch (Exception $e) {
            $msg = $this->l('Error:') . htmlspecialchars($e->getMessage());
            PrestaShopLogger::addLog('Tokens: ' . $msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');

            return [];
        }
    }

    /**
     * @param $id
     *
     * @return bool
     */
    private function isShippingStatus($id)
    {
        return (int) $id === (int) Configuration::get('PS_OS_SHIPPING') ||
            (int) $id === (int) Configuration::get('MULTISAFEPAY_OS_SHIPPED');
    }

    /**
     * @param $string
     * @param $prefix
     *
     * @return bool
     */
    private function startsWith($string, $prefix)
    {
        return strncmp($string, $prefix, strlen($prefix)) === 0;
    }

    /**
     * @param \Order $order
     *
     * @return bool
     */
    private function isMultiSafepayOrder(Order $order)
    {
        return $order->module && $this->startsWith($order->module, 'multisafepay');
    }

    /**
     * Send shipment update to MultiSafepay
     *
     * @param array $params
     */
    public function hookActionOrderStatusPostUpdate(array $params)
    {
        if (!$this->isShippingStatus($params['newOrderStatus']->id)) {
            return;
        }

        $order = new Order((int) $params['id_order']);

        if (!$this->isMultiSafepayOrder($order)) {
            return;
        }

        $shipData = [
            'tracktrace_code' => $order->getWsShippingNumber(),
            'carrier' => (new Carrier((int)$order->id_carrier))->name,
            'ship_date' => date('Y-m-d H:i:s'),
            'status' => 'shipped',
        ];

        $endpoint = 'orders/' . $params['id_order'];

        $multisafepay = new MultiSafepayClient();
        $multisafepay->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $multisafepay->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));
        $multisafepay->orders->patch($shipData, $endpoint);
    }
}
