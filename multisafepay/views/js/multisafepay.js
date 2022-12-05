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
window.addEventListener(
    'load', () => {
    let inProgress = false
    const modals = [...document.querySelectorAll('.multisafepay-modal-container')]

    modals.forEach(
        modal => {
        const gateway = modal.dataset.gateway
        const modalBackdrop = document.querySelector('.modal-backdrop-' + gateway)

        modalBackdrop.addEventListener(
            'click', () => {
            if (!inProgress) {
                hidePaymentModal(gateway)
            }
            }
        )

        document.addEventListener(
            'keyup', (event) => {
            if (!inProgress && event.code === 'Escape') {
                hidePaymentModal(gateway)
            }
            }
        )
        }
    )

    const modalForms = [...document.querySelectorAll('.msp-modal-container form')]
    modalForms.forEach(
        form => {
        form.addEventListener(
            'submit', () => {
            inProgress = true
            const payButton = form.querySelector('.pay-button')
            const cancelButton = form.querySelector('.cancel-button')
            const closeButton = form.querySelector('.close')

            payButton.disabled = true
            cancelButton.disabled = true
            closeButton.disabled = true

            payButton.innerHTML = "Loading..."
            }
        )
        }
    )

    $('.select2').select2();
    }
)

function removeDisabled() {
    const payButton = document.getElementById('payButton')
    payButton.classList.remove('disabled')
}

function showPaymentModal(gateway) {
    const modal = document.getElementById('modal-container-' + gateway)

    document.body.style.overflow = 'hidden'

    modal.classList.remove('modal-closed')
    modal.classList.add('modal-open')
}

function hidePaymentModal(gateway) {
    const modal = document.getElementById('modal-container-' + gateway)

    document.body.style.overflow = 'auto'

    modal.classList.remove('modal-open')
    modal.classList.add('modal-closed')
}

async function deleteToken(token, gateway, removeTokenUrl) {
    let formData = new FormData()
    formData.append('token', token)
    formData.append('ajax', true)

    const response = await fetch(
        removeTokenUrl, {
        method: 'POST',
        body: formData
        }
    )

    const result = await response.json()

    const tokenRadioInputs = document.querySelectorAll('#token-' + token)
    tokenRadioInputs.forEach(
        (tokenRadioInput) => {
        const parent = tokenRadioInput.parentElement

        tokenRadioInput.remove()

        if (parent.children.length < 2) {
            const newDetails = parent.querySelector('#multisafepay-new-details')
            newDetails.checked = true
            newDetails.dispatchEvent(new Event('change'))
        }
        }
    )

    const notification = document.getElementById('multisafepay-' + gateway + '-notification')
    notification.innerHTML = result.message
    notification.style.display = 'block'
}

function showSaveNewCheckbox(gateway) {
    const saveDetails = document.getElementById('save-details-' + gateway)
    saveDetails.style.display = 'block'
}

function dontShowSaveNewCheckbox(gateway) {
    const saveDetails = document.getElementById('save-details-' + gateway)
    saveDetails.style.display = 'none'
}
