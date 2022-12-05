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
class MultisafepayRedirectModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        /* wait maximal 10 seconds to give PrestaShop the time to create the order */
        $i = 0;
        do {
            sleep(1);
            $order = new Order(Order::getOrderByCartId((int) Tools::getValue('id_cart')));
        } while (empty($order->id) && ++$i < 10);

        $url = 'index.php?controller=order-confirmation'
                       . '&key=' . $order->secure_key
                       . '&id_cart=' . $order->id_cart
                       . '&id_module=' . Tools::getValue('id_module')
                       . '&id_order=' . Tools::getValue('id_order');
        Tools::redirect($url);
    }
}
