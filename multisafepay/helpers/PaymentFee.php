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
class PaymentFee
{
    public function getFee($gateway)
    {
        if (Module::isInstalled('bvkpaymentfees')) {
            $fee = Db::getInstance()->getRow('SELECT increment, multiply  FROM `' . _DB_PREFIX_ . 'bvk_paymentfees` WHERE name=\'' . $gateway . '\'');

            if ($fee && !array_key_exists('increment', $fee)) {
                $fee['increment'] = 0;
            }

            if ($fee && !array_key_exists('multiply', $fee)) {
                $fee['multiply'] = 0;
            }

            if ($fee['multiply'] > 0) {
                $fee['multiply'] = ($fee['multiply'] - 1) * 100;
            }

            return $fee;
        }
    }
}
