<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
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
    private $msp_error;

    public function __construct($api, $test_mode)
    {
        $this->msp_error = 'There was a problem with your API key. Please check it and try again.';
        $this->checkConnection($api, $test_mode);
    }

    public function checkConnection($api, $test_mode)
    {
        if (empty($api)) {
            return $this->msp_error;
        }
        return $this->testConnection($api, $test_mode);
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

        $msg = false;
        try {
            $msp->orders->post($test_order);
            $msp->orders->getPaymentLink();
        } catch (Exception $e) {
            $msg = $this->msp_error;
            $check = strip_tags($e->getMessage());
            if (strpos($check, '1032') !== false) {
                $msg = 'API key is not valid. Please check it and try again.';
            }
        }
        return $msg;
    }
}
