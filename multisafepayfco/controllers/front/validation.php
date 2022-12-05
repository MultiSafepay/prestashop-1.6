<?php
/**
 * MultiSafepay Payment Module
 *
 *  @author    MultiSafepay <integration@multisafepay.com>
 *  @copyright Copyright (c) 2013 MultiSafepay (http://www.multisafepay.com)
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
require_once _PS_MODULE_DIR_ . 'multisafepay/controllers/front/validation.php';

class MultisafepayFcoValidationModuleFrontController extends MultisafepayValidationModuleFrontController
{
    public function postProcess()
    {
        $this->plugin_version = '3.9.0';

        $this->api = Configuration::get('MULTISAFEPAY_FCO_API_KEY');
        $this->mode = Configuration::get('MULTISAFEPAY_FCO_SANDBOX');

        $this->type = 'checkout';
        $this->gatewayInfo = '';

        $this->getFcoCart();
        $this->getTransactionExtra();
        $this->getShipping();
        $this->getCustomer();
        $this->startTransaction();
    }

    private function getFcoCart()
    {
        $this->total_price = 0;
        $this->total_weight = 0;
        $this->total_price_incl_tax = 0;

        $this->shopping_cart = [];
        $this->checkout_options = [];

        $cart = $this->context->cart;

        $total_data = $cart->getSummaryDetails();

        $this->checkout_options['tax_tables']['alternate'] = [];

        // Products
        $products = $cart->getProducts();

        foreach ($products as $product) {
            //	  $product[ecotax]
            //    $product[additional_shipping_cost]

            $product['tax_name'] = $product['tax_name'] ? $product['tax_name'] : '0';

            $this->shopping_cart['items'][] = [
                'name' => $product['name'],
                'description' => $product['description_short'],
                'unit_price' => round($product['price'], 4),
                'quantity' => $product['quantity'],
                'merchant_item_id' => $product['id_product'],
                'tax_table_selector' => $product['tax_name'],
                'weight' => ['unit' => $product['weight'],  'value' => 'KG'],
            ];

            $this->total_price = $product['quantity'] * round($product['price'], 4);
            $this->total_weight = $product['quantity'] * $product['weight'];

            $this->total_price_incl_tax += $this->total_price * (1 + $product['rate'] / 100);

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => $product['tax_name'], 'rules' => [['rate' => $product['rate'] / 100]]]);
        }

        // Discount
        if ($total_data['total_discounts'] > 0) {
            $this->shopping_cart['items'][] = [
                'name' => 'Discount',
                'description' => 'Discount',
                'unit_price' => round(-$total_data['total_discounts'], 4),
                'quantity' => 1,
                'merchant_item_id' => 'Discount',
                'tax_table_selector' => 'Discount',
                'weight' => ['unit' => 0,  'value' => 'KG'],
            ];
            $this->total_price_incl_tax += round(-$total_data['total_discounts'], 4);

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => 'Discount', 'rules' => [['rate' => '0.00']]]);
        }

        // Wrapping
        if ($total_data['total_wrapping'] > 0) {
            $this->shopping_cart['items'][] = [
                'name' => 'Wrapping',
                'description' => 'Wrapping',
                'unit_price' => round($total_data['total_wrapping_tax_exc'], 4),
                'quantity' => 1,
                'merchant_item_id' => 'Wrapping',
                'tax_table_selector' => 'Wrapping',
                'weight' => ['unit' => 0,  'value' => 'KG'],
            ];

            $this->total_price_incl_tax += round($total_data['total_wrapping'], 4);
            if ($total_data['total_wrapping_tax_exc'] > 0) {
                $wrapping_tax_percentage = round(($total_data['total_wrapping'] - $total_data['total_wrapping_tax_exc']) * ($total_data['total_wrapping_tax_exc']), 2);
            } else {
                $wrapping_tax_percentage = 0;
            }

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => 'Wrapping', 'rules' => [['rate' => $wrapping_tax_percentage]]]);
        }
    }

    private function getTransactionExtra()
    {
        $extra_data_tmp = [];
        $extra_data_tmp['id_shop'] = $this->context->cart->id_shop;
        $extra_data_tmp['id_lang'] = $this->context->cart->id_lang;
        $extra_data_tmp['id_currency'] = $this->context->cart->id_currency;
        $extra_data_tmp['id_cart'] = $this->context->cart->id;
        $extra_data_tmp['id_module'] = $this->module->id;
        $extra_data_tmp['secure_key'] = $this->context->cart->secure_key;

        $this->var1 = Tools::jsonEncode($extra_data_tmp);
        $this->var2 = $this->context->cart->secure_key;
        $this->var3 = $this->context->cart->id_guest;
    }

    private function getShipping()
    {
        $weight = $this->total_weight;
        $amount = $this->total_price_incl_tax;

        $countrycode = Tools::strtoupper($this->context->country->iso_code);

        $handling_fee = 0;
        $free_shipping_starts_at_weigth = 9999;
        $free_shipping_starts_at_price = 9999;

        // Get some config data
        $sql = 'SELECT name, value FROM ' . _DB_PREFIX_ . 'configuration';
        foreach (Db::getInstance()->ExecuteS($sql) as $config) {
            switch ($config['name']) {
                case 'PS_SHIPPING_HANDLING':
                    $handling_fee = $config['value'];
                    break;
                case 'PS_SHIPPING_FREE_WEIGHT':
                    $free_shipping_starts_at_weigth = $config['value'];
                    break;
                case 'PS_SHIPPING_FREE_PRICE':
                    $free_shipping_starts_at_price = $config['value'];
                    break;
            }
        }

        // Get some extra info.
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;

        $sql = 'SELECT	\'weight\' AS \'based_on\'	,
                        c.id_carrier 		,
                        c.name				,
                        c.is_free			,
                        c.shipping_handling	,
                        cl.delay			,
                        rw.id_range_weight	AS \'range\',
                        rw.delimiter1		,
                        rw.delimiter2		,
                        d.id_delivery		,
                        d.id_zone			,
                        d.price				,
                        tx.rate

                     FROM ' . _DB_PREFIX_ . 'carrier c
                          LEFT OUTER JOIN ' . _DB_PREFIX_ . 'delivery  d      ON c.id_carrier = d.id_carrier
                          LEFT OUTER JOIN ' . _DB_PREFIX_ . 'range_weight rw  ON d.id_range_weight = rw.id_range_weight
                          LEFT OUTER JOIN ' . _DB_PREFIX_ . 'carrier_lang cl  ON c.id_carrier = cl.id_carrier
                          LEFT OUTER JOIN ' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop trgs ON c.id_carrier = trgs.id_carrier
                          LEFT OUTER JOIN ' . _DB_PREFIX_ . 'tax tx ON trgs.id_tax_rules_group = tx.id_tax
                          WHERE 	cl.id_shop = ' . $id_shop . '
                      AND 	cl.id_lang = ' . $id_lang . '
                      AND 	d.id_zone = (SELECT id_zone FROM ' . _DB_PREFIX_ . 'country WHERE iso_code=\'' . $countrycode . '\')
                      AND ' . $weight . ' BETWEEN rw.delimiter1 and rw.delimiter2
                      AND 	c.active  =  1
                      AND 	c.deleted =  0
                      AND 	c.is_free =  0
                      AND       c.name   != "0"
                  UNION

                  SELECT 	\'price\' AS \'based_on\'	,
                        c.id_carrier		,
                        c.name				,
                        c.is_free			,
                        c.shipping_handling	,
                        cl.delay		,
                        rp.id_range_price	AS \'range\',
                        rp.delimiter1		,
                        rp.delimiter2		,
                        d.id_delivery		,
                        d.id_zone			,
                        d.price             ,
                        tx.rate

                  FROM ' . _DB_PREFIX_ . 'carrier c
                        LEFT OUTER JOIN ' . _DB_PREFIX_ . 'delivery  d      ON c.id_carrier = d.id_carrier
                        LEFT OUTER JOIN ' . _DB_PREFIX_ . 'range_price rp   ON d.id_range_price = rp.id_range_price
                        LEFT OUTER JOIN ' . _DB_PREFIX_ . 'carrier_lang cl  ON c.id_carrier = cl.id_carrier
                        LEFT OUTER JOIN ' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop trgs ON c.id_carrier = trgs.id_carrier
                        LEFT OUTER JOIN ' . _DB_PREFIX_ . 'tax tx ON trgs.id_tax_rules_group = tx.id_tax
                    WHERE 	cl.id_shop = ' . $id_shop . '
                      AND 	cl.id_lang = ' . $id_lang . '
                      AND 	d.id_zone = (SELECT id_zone FROM ' . _DB_PREFIX_ . 'country WHERE iso_code=\'' . $countrycode . '\')
                      AND ' . $amount . ' BETWEEN rp.delimiter1 and rp.delimiter2
                      AND 	c.active  =  1
                      AND 	c.deleted =  0
                      AND 	c.is_free =  0
                      AND       c.name   != "0"
                  UNION

                SELECT 	\'free\' 		AS \'based_on\'	,
                        c.id_carrier					,
                        c.name							,
                        c.is_free						,
                        c.shipping_handling				,
                        cl.delay						,
                        0				AS \'range\'	,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                   FROM ' . _DB_PREFIX_ . 'carrier c
                      LEFT OUTER JOIN ' . _DB_PREFIX_ . 'carrier_lang cl  ON c.id_carrier = cl.id_carrier
                   WHERE 	cl.id_shop = ' . $id_shop . '
                     AND 	cl.id_lang = ' . $id_lang . '
                      AND 	c.active  =  1
                      AND 	c.deleted =  0
                      AND 	c.is_free =  1';
//                    AND       c.name   != "0"';

        $carriers = Db::getInstance()->ExecuteS($sql);

        if (count($carriers) == 0) {
            $this->checkout_options['no_shipping_method'] = true;
        } else {
            $this->checkout_options['no_shipping_method'] = false;
        }
        foreach ($carriers as $carrier) {
            $shipping_price = $carrier['price'];
            $carrier_name = $carrier['name'] == '0' ? 'Free' : $carrier['name'];
            $carrier_name .= ' (' . $carrier['delay'] . ')   ';
            if ($carrier_name == '' and $shipping_price == 0) {
                $this->checkout_options['shipping_methods']['pickup'] = ['name' => $carrier_name,
                                                                                'price' => round($shipping_price, 4), ];

                $this->checkout_options['tax_tables']['default'] = ['shipping_taxed' => 'true', 'rate' => 0];
            } else {
                 // include handling fee
                if ($carrier['shipping_handling'] == 1) {
                    $shipping_price += $handling_fee;
                }

                if ($carrier['based_on'] == 'weight' && $free_shipping_starts_at_weigth > 0 && $weight >= $free_shipping_starts_at_weigth) {
                    $shipping_price = 0;
                }

                if ($carrier['based_on'] == 'price' && $free_shipping_starts_at_price > 0 && $amount >= $free_shipping_starts_at_price) {
                    $shipping_price = 0;
                }

                if ($carrier['based_on'] == 'free') {
                    $shipping_price = 0;
                }

                if ($shipping_price == 0) {
                    $shipping_price = '0.00';
                }

                $this->checkout_options['shipping_methods']['flat_rate_shipping'][] = ['name' => $carrier_name,
                                                                                            'price' => $shipping_price, ];
                $this->checkout_options['tax_tables']['default'] = ['shipping_taxed' => 'true', 'rate' => $carrier['rate'] / 100];
            }
        }
    }

    private function getCustomer()
    {
        $this->customer = [];

        // Customer section.....
        $row = ['firstname' => '',
                            'lastname' => '',
                            'email' => '', ];
        $address = ['postcode' => '',
                            'city' => '',
                            'phone' => '', ];
        $state = ['name' => ''];
        $country = ['iso_code' => ''];

        if (isset($this->context->cart->id_customer)) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'customer WHERE id_customer=\'' . $this->context->cart->id_customer . '\'';
            if ($results = Db::getInstance()->ExecuteS($sql)) {
                $row = array_shift($results);
            }

            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_customer=\'' . $this->context->cart->id_customer . '\'';
            if ($adresses = Db::getInstance()->ExecuteS($sql)) {
                $address = array_shift($adresses);

                $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state=\'' . $address['id_state'] . '\'';
                if ($states = Db::getInstance()->ExecuteS($sql)) {
                    $state = array_shift($states);
                }

                $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country=\'' . $address['id_country'] . '\'';
                if ($countries = Db::getInstance()->ExecuteS($sql)) {
                    $country = array_shift($countries);
                }

//                $msp->parseCustomerAddress($address['address1']);
            }
        }

        $this->customer['locale'] = str_replace('-', '_', Language::getLanguageCodeByIso(Language::getIsoById($this->context->cart->id_lang)));
        $this->customer['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $this->customer['referrer'] = $_SERVER['HTTP_REFERER'];
        $this->customer['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

//      $this->customer ['forwarded_ip']    = '';
        $this->customer['first_name'] = $row['firstname'];
        $this->customer['last_name'] = $row['lastname'];
        $this->customer['address1'] = $address['address1'];
//      $this->customer ['address2']        = '';
//      $this->customer ['house_number']    = '';
        $this->customer['zip_code'] = $address['postcode'];
        $this->customer['city'] = $address['city'];
        $this->customer['country'] = $country['iso_code'];
        $this->customer['state'] = $state['name'];
        $this->customer['phone'] = $address['phone'];
        $this->customer['email'] = $row['email'];
    }

    private function startTransaction()
    {
        $currency = Currency::getCurrencyInstance($this->context->cart->id_currency);

        $products = $this->context->cart->getProducts();
        $items = "<ul>\n";
        foreach ($products as $product) {
            $items .= '<li>';
            $items .= $product['cart_quantity'] . ' x : ' . $product['name'];
            if (!empty($product['attributes_small'])) {
                $items .= '(' . $product['attributes_small'] . ')';
            }
            $items .= "</li>\n";
        }
        $items .= "</ul>\n";

        $orderid = $this->context->cart->id . '-' . time();

        $msp = new MultiSafepayClient();

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

        $type = (isset($this->type) ? $this->type : 'redirect');
        $var1 = (isset($this->var1) ? $this->var1 : Tools::jsonEncode(['id_cart' => $this->context->cart->id]));
        $var2 = (isset($this->var2) ? $this->var2 : $this->context->cart->secure_key);
        $var3 = (isset($this->var3) ? $this->var3 : '');

//      $url_prefix = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) ? 'https://' : 'http://';
//      $url_prefix = Configuration::get('MULTISAFEPAY_NURL_MODE') == 'HTTP'  ? 'http://'  : $url_prefix ;
//      $url_prefix = Configuration::get('MULTISAFEPAY_NURL_MODE') == 'HTTPS' ? 'https://' : $url_prefix ;
//      $url_prefix .= htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php';
        $url_prefix = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'index.php';

        $my_order = [
            'type' => $type,
            'order_id' => $orderid,
            'currency' => $currency->iso_code,
            'amount' => round($this->total_price_incl_tax, 2) * 100,
            'description' => 'Order #' . $orderid,
            'var1' => $var1,
            'var2' => $var2,
            'var3' => $var3,
            'items' => $items,
            'manual' => 0,
            'seconds_active' => Configuration::get('MULTISAFEPAY_SECONDS_ACTIVE'),
            'payment_options' => [
                'notification_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=notification&type=initial',
                'redirect_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=redirect' .
                                                                                                    '&key=' . $var2 .
                                                                                                    '&id_cart=' . $this->context->cart->id .
                                                                                                    '&id_module=' . $this->module->id .
                                                                                                    '&id_order=' . $orderid,
                'cancel_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=notification&type=cancel',
                'close_window' => 'true',
            ],
            'customer' => $this->customer,

            'gateway_info' => $this->gatewayInfo,
            'shopping_cart' => $this->shopping_cart,
            'checkout_options' => $this->checkout_options,

            'google_analytics' => [
                'account' => 'UA-XXXXXXXXX',
            ],

            'plugin' => [
                'shop' => 'PrestaShop',
                'shop_version' => 'PrestaShop ' . _PS_VERSION_,
                'plugin_version' => '(' . $this->plugin_version . ')',
                'partner' => '',
                'shop_root_url' => $url_prefix,
            ],

            'custom_info' => [
                'custom_1' => '',
                'custom_2' => '',
            ],
        ];

        try {
            $msp->orders->post($my_order);
            $url = $msp->orders->getPaymentLink();
            Tools::redirect($url);
        } catch (Exception $e) {
            $msg = $this->l('Error:') . htmlspecialchars($e->getMessage());
            PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');

            $Debug = new Debug(print_r($my_order, true));
            $Debug = new Debug($msg);

            $this->errors[] = $msg;

            /* and return back to initContent function */
            return;
        }
    }
}
