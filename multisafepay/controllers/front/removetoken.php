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
class MultisafepayRemovetokenModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }

    public function postProcess()
    {
        $customer_id = $this->context->customer->id;
        if (!isset($customer_id) || !$this->context->customer->isLogged()) {
            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                PrestaShopLogger::addLog(
                    'Remove token controller has been called, but no customer can be found in session',
                    4,
                    '',
                    'MultiSafepay',
                    'MSP',
                    'MSP'
                );
            }
            $this->json = ['success' => false, 'message' => $this->module->l('Something went wrong')];

            return;
        }

        $token = Tools::getValue('token');

        $multisafepay_client = new MultiSafepayClient();
        $multisafepay_client->setApiKey(Configuration::get('MULTISAFEPAY_API_KEY'));
        $multisafepay_client->setApiUrl(Configuration::get('MULTISAFEPAY_SANDBOX'));

        try {
            $multisafepay_client->tokens->delete('', 'recurring/' . $customer_id . '/remove/' . $token);
        } catch (Exception $e) {
            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                PrestaShopLogger::addLog($e->getMessage(), 4, '', 'MultiSafepay', 'MSP', 'MSP');
            }
            $this->json = [
                'success' => false,
                'message' => $this->module->l('Something went wrong'),
            ];
        }

        $this->json = [
            'success' => true,
            'message' => $this->module->l('Successfully deleted saved payment details'),
        ];
    }

    protected function displayAjax()
    {
        exit(Tools::jsonEncode($this->json));
    }
}
