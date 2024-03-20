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
let paymentComponents = []

function initializePaymentComponent(config) {
    const PaymentComponent = new MultiSafepay(
        {
            env: config.env,
            apiToken: config.apiToken,
            order: config.orderData,
            recurring: config.recurring,
        }
    )

    PaymentComponent.init(
        'payment', {
        container: '#multisafepay-payment-component-' + config.gateway.toLowerCase(),
        gateway: config.gateway,
        onLoad: state => {
            if (config.debug) {
                console.log('onLoad', state)
            }

            document.getElementById('componentPayButton').removeAttribute('disabled')
        },
        onError: state => {
            if (config.debug) {
                console.log('onError', state)
            }
        },
        onValidation: () => {
            document.getElementById('componentPayButton').removeAttribute('disabled')
        }
        }
    )

    const instance = [config.gateway, PaymentComponent]

    paymentComponents.push(instance)
}

async function submitPaymentComponent(moduleLink, gateway) {
    let currentPaymentComponent

    document.getElementById('componentPayButton').setAttribute('disabled', "")

    paymentComponents.forEach(
        item => {
        if (gateway === item[0]) {
            currentPaymentComponent = item[1]
        }
        }
    )

    if (currentPaymentComponent.hasErrors()) {
        console.log(currentPaymentComponent.getErrors())
        return
    }

    const paymentData = currentPaymentComponent.getPaymentData()

    const payloadInput = document.querySelector("#multisafepay-form-" + gateway + " input[name='payload']")
    payloadInput.setAttribute("value", paymentData.payload)

    const tokenizeInput = document.querySelector("#multisafepay-form-" + gateway + " input[name='tokenize']")
    tokenizeInput.setAttribute("value", (paymentData.tokenize ?? 0))

    const modalForm = document.getElementById('multisafepay-form-' + gateway)
    modalForm.submit()
}
