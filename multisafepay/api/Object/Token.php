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
class ObjectToken extends ObjectCore
{
    const CREDIT_CARD_GATEWAYS = ['VISA', 'MASTERCARD', 'AMEX', 'MAESTRO'];
    const CREDIT_CARD_GATEWAY_CODE = 'CREDITCARD';

    public function get_by_gateway($gateway_code, $customer_reference)
    {
        $tokens = [];
        $result = $this->get('recurring', $customer_reference);
        if (!$result->success) {
            return $tokens;
        }

        foreach ($result->data->tokens as $token) {
            if ($token->code === $gateway_code) {
                $tokens[] = $token;
            }
            if ($gateway_code === self::CREDIT_CARD_GATEWAY_CODE
                && in_array($token->code, self::CREDIT_CARD_GATEWAYS, true)
            ) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }
}
