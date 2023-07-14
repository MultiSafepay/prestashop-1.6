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
class MultisafepayNotificationModuleFrontController extends ModuleFrontController
{
    public $order_id;
    public $states;
    public $statussen;
    public $cart_id;
    public $secure_key;
    public $paid;
    public $initial;
    public $transactie;
    public $status;

    public function initContent()
    {
        $transactionid = Tools::getValue('transactionid');

        if (Tools::getValue('timestamp')) {
            $this->statussen = [
                'new_order' => Configuration::get('MULTISAFEPAY_OS_NEW_ORDER'),
                'initialized' => Configuration::get('MULTISAFEPAY_OS_INITIALIZED'),
                'completed' => Configuration::get('MULTISAFEPAY_OS_COMPLETED'),
                'uncleared' => Configuration::get('MULTISAFEPAY_OS_UNCLEARED'),
                'cancelled' => Configuration::get('MULTISAFEPAY_OS_CANCELLED'),
                'canceled' => Configuration::get('MULTISAFEPAY_OS_CANCELLED'),
                'void' => Configuration::get('MULTISAFEPAY_OS_VOID'),
                'declined' => Configuration::get('MULTISAFEPAY_OS_DECLINED'),
                'refunded' => Configuration::get('MULTISAFEPAY_OS_REFUNDED'),
                'partial_refunded' => Configuration::get('MULTISAFEPAY_OS_PARTIAL_REFUNDED'),
                'expired' => Configuration::get('MULTISAFEPAY_OS_EXPIRED'),
                'shipped' => Configuration::get('MULTISAFEPAY_OS_SHIPPED'),
            ];

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

            try {
                $this->transactie = $msp->orders->get($transactionid, 'orders', [], false);
            } catch (Exception $e) {
                $msg = sprintf('%s %s', htmlspecialchars($e->getMessage()), $transactionid);
                PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');
                exit('Error: ' . $msg);
            }

            // Test if transaction exist
            if ($this->transactie->order_id) {
                $extra_data = Tools::jsonDecode($this->transactie->var1);

                $this->cart_id = $extra_data->id_cart;
                $this->secure_key = $this->transactie->var2;
                $this->paid = $this->transactie->amount / 100;
                $this->order_id = $this->transactie->order_id;
                $this->status = $this->transactie->status;
                $this->transaction_id = $this->transactie->transaction_id;

                // If timestamp or type=redirect, process, otherwise not
                if (Tools::getValue('timestamp') || Tools::getValue('type') == 'redirect') {
                    switch (Configuration::get('MULTISAFEPAY_WHEN_CREATE_ORDER')) {
                        case 'After_Confirmation':
                            $this->afterConfirmation();
                            break;
                        case 'After_Payment_Complete':
                            $this->afterPaymentComplete();
                            break;
                        case 'After_Payment_Complete_Inc_Banktrans':
                            $this->afterPaymentCompleteIncBanktrans();
                            break;
                    }
                }
            } elseif (Tools::getValue('payload_type') === 'pretransaction') {
                $this->cart_id = Tools::getValue('id_cart');
                $this->status = $this->transactie->status;
                $this->updateOrder();
            }
        }

        switch (Tools::getValue('type')) {
            case 'initial':
                exit;
            case 'redirect':
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $this->cart_id . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->order_id . '&key=' . $this->secure_key);
                break;
            case 'cancel':
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

                if($transactionid) {
                    try {
                        $this->transactie = $msp->orders->get($transactionid, 'orders', [], false);
                    } catch (Exception $e) {
                        $msg = sprintf('%s %s', htmlspecialchars($e->getMessage()), $transactionid);
                        PrestaShopLogger::addLog($msg, 4, '', 'MultiSafepay', 'MSP', 'MSP');
                    }

                    // Test if transaction exist
                    if ($this->transactie->order_id) {
                        $var1 = Tools::jsonDecode($this->transactie->var1);
                        $cart_id = $var1->id_cart;

                        $lastCart = new Cart($cart_id);
                        $newCart = $lastCart->duplicate();

                        if (!$newCart || !Validate::isLoadedObject($newCart['cart'])) {
                            $errors[] = Tools::displayError('Sorry. We cannot renew your order.');
                        } elseif (!$newCart['success']) {
                            $errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
                        } else {
                            $this->context->cookie->id_cart = $newCart['cart']->id;
                            $this->context->cookie->write();
                        }
                    }
                } else {
                    $cart_id = Tools::getValue('id_cancel_cart');
                    $lastCart = new Cart($cart_id);
                    $newCart = $lastCart->duplicate();

                    if (!$newCart || !Validate::isLoadedObject($newCart['cart'])) {
                        $errors[] = Tools::displayError('Sorry. We cannot renew your order.');
                    } elseif (!$newCart['success']) {
                        $errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
                    } else {
                        $this->context->cookie->id_cart = $newCart['cart']->id;
                        $this->context->cookie->write();
                    }
                }

                $errors[] = Tools::displayError('Error during payment.');
                $errors[] = Tools::displayError('Please try again..');

                $this->context->cookie->msp_error = serialize($errors);
                $this->context->cookie->write();

                $url = 'index.php?controller=order&step=3';

                Tools::redirect($url);
                exit;
            default:
                exit('ok');
        }
    }

    private function afterConfirmation()
    {
        $this->updateOrder();
    }

    private function afterCheckoutComplete()
    {
        $this->updateOrCreateOrder();
    }

    private function afterPaymentComplete()
    {
        if ($this->status == 'completed') {
            $this->updateOrCreateOrder();
        }

        if ($this->transactie->payment_details->type == 'BANKTRANS') {
            $this->context->cart->delete();
        }
    }

    private function afterPaymentCompleteIncBanktrans()
    {
        if ($this->status == 'completed' || $this->transactie->payment_details->type == 'BANKTRANS') {
            $this->updateOrCreateOrder();
        }

        if ($this->transactie->payment_details->type == 'BANKTRANS') {
            $this->context->cart->delete();
        }
    }

    private function updateOrCreateOrder()
    {
        if ($this->cart_id && Order::getOrderByCartId((int) $this->cart_id)) {
            $this->updateOrder();
        } else {
            $this->createOrder();
        }
    }

    private function updateOrder()
    {
        // Order should already exists
        $orderCollection = new PrestaShopCollection('Order');
        $orderCollection->where('id_cart', '=', (int) $this->cart_id);

        foreach ($orderCollection->getResults() as $order) {
            $history = new OrderHistory();
            $history->id_order = (int) $order->id;

            // If current order-state is not a MultiSafepay order-state, then do not update the status anymore.
            if (!array_search($order->getCurrentState(), $this->statussen)) {
                return;
            }

            if ($order->getCurrentState() != $this->statussen[$this->status]) {
                $history->changeIdOrderState((int) $this->statussen[$this->status], $order->id);
                $history->add();
            }

            if ($this->status == 'completed') {
                $payments = $order->getOrderPaymentCollection();
                $payments[0]->transaction_id = $this->transaction_id;
                $payments[0]->update();
            }
        }
    }

    private function createOrder()
    {
        $paymentMethodName = $this->module->gatewayTitle ?: $this->module->displayName;

        $msg = 'MultiSafepay reference: ' . $this->order_id;
        $createOrder = $this->module->validateOrder((int) $this->cart_id, $this->statussen[$this->status], $this->paid, $paymentMethodName, $msg, [], null, false, $this->secure_key);

        if ($createOrder) {
            $this->updateOrder();
        }
    }
}
