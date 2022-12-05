<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/notification.php';

class MultisafepayFcoNotificationModuleFrontController extends MultisafepayNotificationModuleFrontController
{
    public function postProcess()
    {
        $msp = new MultiSafepayClient();

        $this->api = Configuration::get('MULTISAFEPAY_FCO_API_KEY');
        $this->mode = Configuration::get('MULTISAFEPAY_FCO_SANDBOX');

        if (isset($this->api)) {
            // Use API/ mode from the specific gateway configuration
            $api = $this->api;
            $mode = $this->mode;
        } else {
            // Use API/ mode from the default configuration
            $api = Configuration::get('MULTISAFEPAY_API_KEY');
            $mode = Configuration::get('MULTISAFEPAY_SANDBOX');
        }
        $msp->setApiKey($api);
        $msp->setApiUrl($mode);

        $this->transactie = $this->getTransactionStatus($msp);

        $this->updateOrder();

        parent::postProcess();
    }

    private function getTransactionStatus($msp)
    {
        $order_id = Tools::getValue('transactionid');
        try {
            $transactie = $msp->orders->get($order_id, $type = 'orders', $body = [], $query_string = false);
        } catch (Exception $e) {
            $msg = sprintf('%s %s', htmlspecialchars($e->getMessage()), $transactionid);
            PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');
            $Debug = new Debug($msg);

            echo $msg;
        }

        return $transactie;
    }

    private function updateOrder()
    {
        $initial = (!empty($_REQUEST['type']) ? $_REQUEST['type'] : '');
        $order_id = Tools::getValue('transactionid');
        $order_id = strstr($order_id, '-', true);

        $statussen = ['new_order' => Configuration::get('MULTISAFEPAY_OS_NEW_ORDER'),
                             'initialized' => Configuration::get('MULTISAFEPAY_OS_INITIALIZED'),
                             'completed' => Configuration::get('MULTISAFEPAY_OS_COMPLETED'),
                             'uncleared' => Configuration::get('MULTISAFEPAY_OS_UNCLEARED'),
                             'cancelled' => Configuration::get('MULTISAFEPAY_OS_CANCELLED'),
                             'void' => Configuration::get('MULTISAFEPAY_OS_VOID'),
                             'declined' => Configuration::get('MULTISAFEPAY_OS_DECLINED'),
                             'refunded' => Configuration::get('MULTISAFEPAY_OS_REFUNDED'),
                             'partial_refunded' => Configuration::get('MULTISAFEPAY_OS_PARTIAL_REFUNDED'),
                             'expired' => Configuration::get('MULTISAFEPAY_OS_EXPIRED'),
                             'shipped' => Configuration::get('MULTISAFEPAY_OS_SHIPPED'),
                            ];

        $extra_data = Tools::jsonDecode($this->transactie->var1);
        $cart_id = $extra_data->id_cart;
        $module_id = $extra_data->id_module;
        $id_lang = $extra_data->id_lang;
        $secure_key = $this->transactie->var2;
        $paid = $this->transactie->amount / 100;
        $status = $this->transactie->status;
        $payment_description = 'MultiSafepay ' . $this->transactie->payment_details->type;

        // Shipping...
        // ===========
        $carrier_id = null;

        $shipping_method = $this->transactie->order_adjustment->shipping->flat_rate_shipping->name;
        $sql = 'SELECT id_carrier FROM ' . _DB_PREFIX_ . 'carrier WHERE name=\'' . $shipping_method . '\' and active = 1 and deleted = 0';
        if ($carriers = Db::getInstance()->ExecuteS($sql)) {
            $carrier = array_shift($carriers);
            $carrier_id = $carrier['id_carrier'];
        }

        $customer = null;
        if ($secure_key != 0) {
            $customer = new Customer($secure_key);
        } else {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE active = 1 AND email=\'' . $this->transactie->customer->email . '\'';
            if ($customers = Db::getInstance()->ExecuteS($sql)) {
                $data = array_shift($customers);
                $customer = new Customer($data['id_customer']);
                $secure_key = $customer->secure_key;
            } else {
                $customer = new Customer();
                $customer->email = $this->transactie->customer->email;
                $customer->lastname = trim(htmlspecialchars($this->transactie->customer->last_name));
                $customer->firstname = trim(htmlspecialchars($this->transactie->customer->first_name));
                $cleanpass = Tools::passwdGen();
                $customer->passwd = Tools::encrypt($cleanpass);
                $customer->add();
                $secure_key = $customer->secure_key;
                Mail::Send((int) $id_lang, 'account', 'Welcome!', [
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{email}' => $customer->email,
                    '{passwd}' => $cleanpass,
                ], $customer->email, $customer->firstname . ' ' . $customer->lastname);
            }
        }

        $id_billing_address = null;
        $id_shipping_address = null;
        $addresses = $customer->getAddresses($id_lang);

        foreach ($addresses as $address) {
            if ($address['alias'] == 'Multisafepay Billing' &&
                $address['id_country'] == Country::getByIso($this->transactie->customer->country) &&
                $address['lastname'] == trim(htmlspecialchars($this->transactie->customer->last_name)) &&
                $address['firstname'] == trim(htmlspecialchars($this->transactie->customer->first_name)) &&
                $address['address1'] == trim(htmlspecialchars($this->transactie->customer->address1)) . ' ' . trim(htmlspecialchars($this->transactie->customer->house_number)) &&
                $address['city'] == trim(htmlspecialchars($this->transactie->customer->city)) &&
                $address['postcode'] == trim(htmlspecialchars($this->transactie->customer->zip_code)) &&
                $address['active'] == 1 &&
                $address['deleted'] == 0) {
                $id_billing_address = $address['id_address'];
            }

            if ($address['alias'] == 'Multisafepay Shipping' &&
                $address['id_country'] == Country::getByIso($this->transactie->delivery->country) &&
                $address['lastname'] == trim(htmlspecialchars($this->transactie->delivery->last_name)) &&
                $address['firstname'] == trim(htmlspecialchars($this->transactie->delivery->first_name)) &&
                $address['address1'] == trim(htmlspecialchars($this->transactie->delivery->address1)) . ' ' . trim(htmlspecialchars($this->transactie->delivery->house_number)) &&
                $address['city'] == trim(htmlspecialchars($this->transactie->delivery->city)) &&
                $address['postcode'] == trim(htmlspecialchars($this->transactie->delivery->zip_code)) &&
                $address['active'] == 1 &&
                $address['deleted'] == 0) {
                $id_shipping_address = $address['id_address'];
            }
        }

        if (!$id_billing_address) {
            $address = new Address();

            $address->alias = 'Multisafepay Billing';
            $address->id_country = Country::getByIso($this->transactie->customer->country);
            $address->lastname = trim(htmlspecialchars($this->transactie->customer->last_name));
            $address->firstname = trim(htmlspecialchars($this->transactie->customer->first_name));
            $address->address1 = trim(htmlspecialchars($this->transactie->customer->address1)) . ' ' . trim(htmlspecialchars($this->transactie->customer->house_number));
            $address->city = trim(htmlspecialchars($this->transactie->customer->city));
            $address->postcode = $this->transactie->customer->zip_code;
            $address->id_customer = $customer->id;
            $address->add();
            $id_billing_address = $address->id;
        }

        if (!$id_shipping_address) {
            $address = new Address();

            $address->alias = 'Multisafepay Shipping';
            $address->id_country = Country::getByIso($this->transactie->delivery->country);
            $address->lastname = trim(htmlspecialchars($this->transactie->delivery->last_name));
            $address->firstname = trim(htmlspecialchars($this->transactie->delivery->first_name));
            $address->address1 = trim(htmlspecialchars($this->transactie->delivery->address1)) . ' ' . trim(htmlspecialchars($this->transactie->delivery->house_number));
            $address->city = trim(htmlspecialchars($this->transactie->delivery->city));
            $address->postcode = $this->transactie->delivery->zip_code;
            $address->id_customer = $customer->id;
            $address->add();
            $id_shipping_address = $address->id;
        }

        $cart = new Cart((int) $cart_id);

        $cart->id_customer = $customer->id;
        $cart->id_guest = $this->transactie->var3;
        $cart->id_address_invoice = $id_billing_address;
        $cart->id_address_delivery = $id_shipping_address;

        $delivery_option_list = $cart->getDeliveryOptionList();

        if (count($delivery_option_list) == 1) {
            $key = $carrier_id . ',';
            foreach ($delivery_option_list as $id_address => $options) {
                if (isset($options[$key])) {
                    $cart->id_carrier = $carrier_id;
                    $cart->setDeliveryOption([$id_address => $key]);
                }
            }
        }

        $cart->update();

        if (Order::getOrderByCartId($cart_id)) {
            $order = new Order(Order::getOrderByCartId($cart_id));
            if (in_array($order->getCurrentState(), $statussen)) {
                $history = new OrderHistory();
                $history->id_order = (int) $order->id;

                if ($order->getCurrentState() != $statussen[$status]) {
                    $history->changeIdOrderState((int) $statussen[$status], $order->id);
                    $history->add();
                }
            }
        } else {
            $this->module->validateOrder((int) $cart_id, $statussen[$status], $paid, $payment_description, null, [], null, false, $secure_key);
        }

        $sql = 'UPDATE ' . _DB_PREFIX_ . 'orders SET id_address_delivery = \'' . $id_shipping_address . '\'' .
                                              ', id_address_invoice  = \'' . $id_billing_address . '\'' .
                                              ', payment             = \'' . $payment_description . '\'  WHERE id_cart = \'' . $cart_id . '\'';
        Db::getInstance()->Execute($sql);

        $url = 'index.php?controller=order-confirmation&id_cart=' . (int) ($cart_id) . '&id_module=' . (int) ($module_id) . '&id_order=' . $order_id . '&key=' . $secure_key;

        switch ($initial) {
            case 'initial':
                $returl = '<a href="' . $url . '" />Return to webshop..</a>';
                exit($returl);
                break;
            case 'redirect':
                Tools::redirect($url);
                break;
            default:
                exit('ok');
        }
    }
}
