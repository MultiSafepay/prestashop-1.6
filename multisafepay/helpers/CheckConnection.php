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
class CheckConnection
{
    public function __construct($api, $test_mode)
    {
        if ($api == '') {
            return;
        }
        // Test with current mode
        $msg = $this->testConnection($api, $test_mode);
        if ($msg == null) {
            return;
        }

        // Test with oposite mode
        $msg = $this->testConnection($api, !$test_mode);
        if ($msg == null) {
            return $test_mode ? 'This API-Key belongs to a LIVE-account' : 'This API-Key belongs to a TEST-account';
        }

        return 'Unknown error. Probably the API-Key is not correct. Error-code: ' . $msg;
    }

    public function checkConnection($api, $test_mode)
    {
        return self::__construct($api, $test_mode);
    }

    private function testConnection($api, $test_mode)
    {
        $test_order = [
            'type' => 'redirect',
            'order_id' => 'Check Connection-' . time(),
            'currency' => 'EUR',
            'amount' => 1,
            'description' => 'Check Connection-' . time(),
        ];

        $msp = new MultiSafepayClient();
        $msp->setApiKey($api);
        $msp->setApiUrl($test_mode);

        $msg = null;

        try {
            $msp->orders->post($test_order);
            $url = $msp->orders->getPaymentLink();
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }

        return $msg;
    }
}
