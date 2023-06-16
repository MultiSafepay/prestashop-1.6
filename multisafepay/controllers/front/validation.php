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
class MultisafepayValidationModuleFrontController extends ModuleFrontController
{
    public $fee;
    public $display_column_left = false;
    public $display_column_right = false;
    public $gateway = null;

    public function postProcess()
    {
        $plugin_version = '3.10.0';

        if ($this->context->cart->id_customer == 0
            || $this->context->cart->id_address_delivery == 0
            || $this->context->cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $this->getCart();

        if (!isset($this->shopping_cart)) {
            $this->shopping_cart = [];
        }
        if (!isset($this->checkout_options)) {
            $this->checkout_options = [];
        }

        $secure_key = $customer->secure_key;

        if (Tools::getValue('component')) {
            $paymentComponentData = $this->postProcessPaymentComponent();
            if ($paymentComponentData && isset($paymentComponentData['has_errors'])) {
                return;
            }
        }

        $gateway = $this->gateway ?: Tools::getValue('gateway');
        $gateway = $gateway == 'CONNECT' ? '' : $gateway;

        $address_invoice = new Address((int) $this->context->cart->id_address_invoice);
        $country_invoice = new Country((int) $address_invoice->id_country);
        $state_id = (int) $address_invoice->id_state;
        if ($state_id > 0) {
            $state = new State((int) $address_invoice->id_state);
            $state_code_invoice = $state->iso_code;
        } else {
            $state_code_invoice = '';
        }

        $address_delivery = new Address((int) $this->context->cart->id_address_delivery);
        $country_delivery = new Country((int) $address_delivery->id_country);
        $state_id = (int) $address_invoice->id_state;
        if ($state_id > 0) {
            $state = new State((int) $address_invoice->id_state);
            $state_code_delivery = $state->iso_code;
        } else {
            $state_code_delivery = '';
        }

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

        $fee = $this->module->fee;
        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $totalFee = 0;
        if ($fee['multiply'] > 0) {
            $totalFee = ($total * ($fee['multiply'] / 100 + 1)) - $total;
        }
        if ($fee['increment'] > 0) {
            $totalFee += $fee['increment'];
        }
        $total = round($total, 2);

        if (isset($totalFee) && $totalFee > 0) {
            $items .= '<li>';
            $items .= '1 x : toeslag voor betalen met ' . $gateway . ' ( ' . $currency->iso_code . ' ' . number_format((float) $totalFee, 2, '.', '') . ')';
            $items .= "</li>\n";
        }
        $items .= "</ul>\n";

        if (Configuration::get('MULTISAFEPAY_WHEN_CREATE_ORDER') == 'After_Confirmation') {
            $paymentMethodName = $this->module->gatewayTitle ?: $this->module->displayName;
            $this->module->validateOrder((int) $this->context->cart->id, Configuration::get('MULTISAFEPAY_OS_INITIALIZED'), $total, $paymentMethodName, null, [], null, false, $secure_key);
            $orderid = (int) $this->module->currentOrder;
        } else {
            $rnd_id = crypt(uniqid(rand(), 1), 'msp');
            $rnd_id = strip_tags(Tools::stripslashes($rnd_id));
            $rnd_id = str_replace('.', '', $rnd_id);
            $rnd_id = strrev(str_replace('/', '', $rnd_id));
            $random_id_length = 8;
            $orderid = Tools::substr($rnd_id, 0, $random_id_length);
        }

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

        list($street_invoice, $house_number_invoice) = $msp->parseCustomerAddress($address_invoice->address1, $address_invoice->address2);
        list($street_delivery, $house_number_delivery) = $msp->parseCustomerAddress($address_delivery->address1, $address_delivery->address2);

        $type = (isset($this->type) ? $this->type : 'redirect');
        $var1 = (isset($this->var1) ? $this->var1 : Tools::jsonEncode(['id_cart' => $this->context->cart->id]));
        $var2 = (isset($this->var2) ? $this->var2 : $secure_key);
        $var3 = (isset($this->var3) ? $this->var3 : '');

        if (Tools::getValue('phone')) {
            $phone_invoice = Tools::getValue('phone');
            $phone_delivery = Tools::getValue('phone');
        } else {
            $phone_invoice = (isset($address_invoice->phone) ? $address_invoice->phone : $address_invoice->phone_mobile);
            $phone_delivery = (isset($address_delivery->phone) ? $address_delivery->phone : $address_delivery->phone_mobile);
        }

        if (Tools::getValue('birthday')) {
            $birthday = Tools::getValue('birthday');
        } else {
            $birthday = ($customer->birthday == '0000-00-00' ? null : $customer->birthday);
        }

        $token = Tools::getValue('token');
        if ($token === '_new') {
            $newToken = true;
        }

        // Swap format to DD-MM-YYYY
        $birthday = preg_replace("/(^(\d{4}).(\d{2}).(\d{2}))/", '$4-$3-$2', $birthday);

        $url_prefix = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'index.php';

        $my_order = [
              'type' => $type,
              'order_id' => $orderid,
              'currency' => $currency->iso_code,
              'amount' => $total * 100,
              'description' => 'Order #' . $orderid,
              'var1' => $var1,
              'var2' => $var2,
              'var3' => $var3,
              'items' => $items,
              'manual' => 0,
              'gateway' => $gateway,
              'seconds_active' => Configuration::get('MULTISAFEPAY_SECONDS_ACTIVE'),
              'payment_options' => [
                  'notification_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=notification&id_cart=' . $this->context->cart->id . '&type=initial',
                  'redirect_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=redirect' .
                                                                                                   '&key=' . $secure_key .
                                                                                                   '&id_cart=' . $this->context->cart->id .
                                                                                                   '&id_module=' . $this->module->id .
                                                                                                   '&id_order=' . $orderid,
                  /* adviva */
                  /* need cart id */
                  'cancel_url' => $url_prefix . '?fc=module&module=' . $this->module->name . '&controller=notification&type=cancel&id_cancel_cart=' . $this->context->cart->id,
                  /* adviva */

                  'close_window' => 'true',
              ],

              'customer' => [
                  'locale' => $this->getLanguageCode(Language::getIsoById($this->context->cart->id_lang)),
                  'ip_address' => $_SERVER['REMOTE_ADDR'],
                  'forwarded_ip' => '',
                  'first_name' => $address_invoice->firstname,
                  'last_name' => $address_invoice->lastname,
                  'address1' => $street_invoice,
                  'address2' => '',
                  'house_number' => $house_number_invoice,
                  'zip_code' => $address_invoice->postcode,
                  'city' => $address_invoice->city,
                  'state' => $state_code_invoice,
                  'country' => $country_invoice->iso_code,
                  'phone' => $phone_invoice,
                  'birthday' => $birthday,
                  'email' => $customer->email,
              ],

              'delivery' => [
                  'first_name' => $address_delivery->firstname,
                  'last_name' => $address_delivery->lastname,
                  'address1' => $street_delivery,
                  'address2' => '',
                  'house_number' => $house_number_delivery,
                  'zip_code' => $address_delivery->postcode,
                  'city' => $address_delivery->city,
                  'state' => $state_code_delivery,
                  'country' => $country_delivery->iso_code,
                  'phone' => $phone_delivery,
              ],

              'gateway_info' => (isset($this->gatewayInfo) ? $this->gatewayInfo : []),
              'shopping_cart' => (isset($this->shopping_cart) ? $this->shopping_cart : []),
              'checkout_options' => (isset($this->checkout_options) ? $this->checkout_options : []),

              'plugin' => [
                  'shop' => 'PrestaShop',
                  'shop_version' => 'PrestaShop ' . _PS_VERSION_,
                  'plugin_version' => '(' . $plugin_version . ')',
                  'partner' => '',
                  'shop_root_url' => $url_prefix,
              ],
              'custom_info' => [
                  'custom_1' => '',
                  'custom_2' => '',
              ],
        ];

        if ($paymentComponentData) {
            $my_order['payment_data'] = [];
            $my_order['payment_data']['payload'] = $paymentComponentData['payload'];
            $my_order['type'] = 'direct';
        }

        $token = Tools::getValue('token');
        $saveDetails = (bool) Tools::getValue('save_details');

        if ($saveDetails || $token) {
            $my_order = $this->createInitialTokenizedOrder($my_order);

            if ($token) {
                $my_order = $this->createSubsequentTokenizedOrder($my_order, $token);
            }
        }

        try {
            $msp->orders->post($my_order);
            $url = $msp->orders->getPaymentLink();
            Tools::redirect($url);
        } catch (Exception $e) {
            $msp_error = $e->getMessage();

            if ($gateway == 'PAYAFTER') {
                switch ($msp_error) {
                    case '1024: Wrong bankaccount.':
                        $msp_error = 'Uw banknummer is niet correct';
                        break;
                    case '1024: Wrong age.':
                        $msp_error = 'U dient minimaal 16 jaar te zijn om een bestelling te mogen plaatsen';
                        break;
                    case '1027: Totaalbedrag van de winkelwagen moet gelijk zijn aan het bedrag van de transactie.':
                        $msp_error .= ' Dit betreft een technisch probleem. U kunt de webwinkelier vragen om contact op te nemen met hun betaalprovider MultiSafepay';
                        break;
                    default:
                        $msp_error = 'Het spijt ons u te moeten mededelen dat uw aanvraag om uw bestelling op rekening te betalen niet door MultiFactor is geaccepteerd. Voor vragen over uw afwijzing kunt u (minimaal 2 uur na uw afwijzing) contact opnemen met de klantenservice van MultiFactor via telefoonnummer 020 8500 533 of via support@multifactor.nl.';
                        break;
                }
            }

            $msg = $this->module->l('Error:') . htmlspecialchars($e->getMessage());
            PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');

            $Debug = new Debug(print_r($my_order, true));
            $Debug = new Debug($msg);

            $lastCart = new Cart(Order::getCartIdStatic($orderid, $this->context->customer->id));
            $newCart = $lastCart->duplicate();

            if (!$newCart || !Validate::isLoadedObject($newCart['cart'])) {
                $this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
            } elseif (!$newCart['success']) {
                $this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
            } else {
                $this->context->cookie->id_cart = $newCart['cart']->id;
                $this->context->cookie->write();
            }

            $this->errors[] = Tools::displayError($msp_error);
            $this->context->cookie->msp_error = serialize($this->errors);

            Tools::redirect('index.php?controller=order&step=3');

            return;
        }
    }

    public function postProcessPaymentComponent()
    {
        if (!empty(Tools::getValue('payload'))) {
            return ['payload' => Tools::getValue('payload')];
        }

        return ['has_errors' => true];
    }

    public function getCart()
    {
        $cart = $this->context->cart;
        $cartSummary = $cart->getSummaryDetails();

        $this->shopping_cart = [];
        $this->checkout_options = [];
        $this->checkout_options['tax_tables']['default'] = ['shipping_taxed' => 'true', 'rate' => '0.21'];
        $this->checkout_options['tax_tables']['alternate'][] = '';

        $items = "<ul>\n";

        // Products
        foreach ($cartSummary['products'] as $product) {
            $items .= '<li>';
            $items .= $product['cart_quantity'] . ' x : ' . $product['name'];
            if (!empty($product['attributes_small'])) {
                $items .= '(' . $product['attributes_small'] . ')';
            }
            $items .= "</li>\n";

            $product['tax_name'] = $product['tax_name'] ? $product['tax_name'] : '0';
            $this->shopping_cart['items'][] = [
                'name' => $product['name'] . ($product['attributes_small'] ? ' - ' . $product['attributes_small'] : ''),
                'description' => $product['description_short'],
                'unit_price' => round($product['price'], 4),
                'quantity' => $product['cart_quantity'],
                'merchant_item_id' => $product['id_product'] . ($product['id_product_attribute'] ? '_' . $product['id_product_attribute'] : ''),
                'tax_table_selector' => $product['tax_name'],
                'weight' => ['unit' => $product['weight'], 'value' => 'KG'],
            ];

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => $product['tax_name'], 'rules' => [['rate' => $product['rate'] / 100]]]);
        }

        // Gift Products
        foreach ($cartSummary['gift_products'] as $product) {
            $items .= '<li>';
            $items .= $product['cart_quantity'] . ' x : ' . $product['name'];
            if (!empty($product['attributes_small'])) {
                $items .= '(' . $product['attributes_small'] . ')';
            }
            $items .= "</li>\n";

            $product['tax_name'] = $product['tax_name'] ? $product['tax_name'] : '0';
            $this->shopping_cart['items'][] = [
                'name' => $product['name'] . ' (Gift!)',
                'description' => $product['description_short'],
                'unit_price' => round($product['price'], 4),
                'quantity' => $product['cart_quantity'],
                'merchant_item_id' => $product['id_product'] . '_Free',
                'tax_table_selector' => $product['tax_name'],
                'weight' => ['unit' => $product['weight'], 'value' => 'KG'],
            ];
            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => $product['tax_name'], 'rules' => [['rate' => $product['rate'] / 100]]]);
        }

        // Fee  ( BVK PaymentFee)
        $bvkFeeModule = Module::getInstanceByName('bvkpaymentfees');
        if ($bvkFeeModule) {
            $fee = $bvkFeeModule->getFee($this->module->name);

            if ($fee) {
                $this->shopping_cart['items'][] = [
                    'name' => 'Fee',
                    'description' => 'Fee',
                    'unit_price' => round($fee['feeamount_tax_excl'], 4),
                    'quantity' => 1,
                    'merchant_item_id' => 'Fee',
                    'tax_table_selector' => 'Fee',
                    'weight' => ['unit' => 0, 'value' => 'KG'],
                ];

                if ($fee['feeamount'] > 0) {
                    $taxPercentage = round(($fee['feeamount'] / $fee['feeamount_tax_excl']) - 1, 2);
                } else {
                    $taxPercentage = 0;
                }
                array_push($this->checkout_options['tax_tables']['alternate'], ['name' => 'Fee', 'rules' => [['rate' => $taxPercentage]]]);
            }
        }

        // Discount
        foreach ($cartSummary['discounts'] as $discount) {
            $this->shopping_cart['items'][] = [
                'name' => $discount['name'],
                'description' => $discount['description'],
                'unit_price' => round(-$discount['value_tax_exc'], 4),
                'quantity' => 1,
                'merchant_item_id' => 'Discount',
                'tax_table_selector' => $discount['name'],
                'weight' => ['unit' => 0, 'value' => 'KG'],
            ];

            $taxPercentage = 0;

            if ($discount['value_real'] > 0) {
                $taxPercentage = round(($discount['value_real'] / $discount['value_tax_exc']) - 1, 2);
            }

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => $discount['name'], 'rules' => [['rate' => $taxPercentage]]]);
        }

        // Wrapping
        if ($cartSummary['total_wrapping'] > 0) {
            $this->shopping_cart['items'][] = [
                'name' => 'Wrapping',
                'description' => 'Wrapping',
                'unit_price' => round($cartSummary['total_wrapping_tax_exc'], 4),
                'quantity' => 1,
                'merchant_item_id' => 'Wrapping',
                'tax_table_selector' => 'Wrapping',
                'weight' => ['unit' => 0, 'value' => 'KG'],
            ];

            $taxPercentage = round(($cartSummary['total_wrapping'] / $cartSummary['total_wrapping_tax_exc']) - 1, 2);
            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => 'Wrapping', 'rules' => [['rate' => $taxPercentage]]]);
        }

        // Shipping
        if ($cartSummary['carrier']) {
            $this->shopping_cart['items'][] = [
                'name' => $cartSummary['carrier']->name,
                'description' => $cartSummary['carrier']->delay,
                'unit_price' => round($cartSummary['total_shipping_tax_exc'], 4),
                'quantity' => 1,
                'merchant_item_id' => 'msp-shipping',
                'tax_table_selector' => 'Shipping',
                'weight' => ['unit' => 0, 'value' => 'KG'],
            ];

            if ($cartSummary['total_shipping'] > 0) {
                $taxPercentage = round(($cartSummary['total_shipping'] / $cartSummary['total_shipping_tax_exc']) - 1, 2);
            } else {
                $taxPercentage = 0;
            }

            array_push($this->checkout_options['tax_tables']['alternate'], ['name' => 'Shipping', 'rules' => [['rate' => $taxPercentage]]]);
        }

        $items .= "</ul>\n";
    }

    private function getLanguageCode($iso_code)
    {
        $locale = Language::getLanguageCodeByIso($iso_code);
        $parts = explode('-', $locale);
        $language_code = $parts[0] . '_' . strtoupper($parts[1]);

        return $language_code;
    }

    /**
     * @param array $order
     *
     * @return array
     */
    private function createInitialTokenizedOrder($order)
    {
        $order['customer']['reference'] = $this->context->cart->id_customer;
        $order['recurring_model'] = 'cardOnFile';

        return $order;
    }

    /**
     * @param array $order
     *
     * @return array
     */
    private function createSubsequentTokenizedOrder($order, $token)
    {
        $order['type'] = 'direct';
        $order['customer']['reference'] = $this->context->cart->id_customer;
        $order['recurring_id'] = $token;

        return $order;
    }
}
