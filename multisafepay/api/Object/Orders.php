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
class ObjectOrders extends ObjectCore
{
    public $success;
    public $data;

    public function patch($body, $endpoint = '')
    {
        $result = parent::patch(Tools::jsonEncode($body), $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;

        return $result;
    }

    public function get($id, $type = 'orders', $body = [], $query_string = false)
    {
        $result = parent::get($type, $id, $body, $query_string);
        $this->success = $result->success;
        $this->data = $result->data;

        return $this->data;
    }

    public function post($body, $endpoint = 'orders')
    {
        $result = parent::post(Tools::jsonEncode($body), $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;

        return $this->data;
    }

    /**
     * @throws Exception
     */
    public function getPaymentLink()
    {
        $url = $this->data->payment_url;
        if (empty($url)) {
            throw new Exception('Payment URL is empty');
        }

        return $url;
    }
}
