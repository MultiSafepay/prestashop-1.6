{*
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
*}
<div class="multisafepay-modal-container" id="modal-container-{$gateway|escape:'htmlall':'UTF-8'}" data-gateway="{$gateway|escape:'htmlall':'UTF-8'}">
    <div class="multisafepay-modal-backdrop modal-backdrop-{$gateway|escape:'htmlall':'UTF-8'}"></div>
    <div class="multisafepay-modal paymentModal" id="paymentModal-{$gateway|escape:'htmlall':'UTF-8'}">
        <div class="multisafepay-modal-dialog">
            <form
                    action="{$link->getModuleLink({$moduleLink|escape:'htmlall':'UTF-8'}, 'validation', [], true)|escape:'htmlall':'UTF-8'}"
                    method="POST"
                    id="multisafepay-form-{$gateway|escape:'htmlall':'UTF-8'}"
            >
                <input type="hidden" name="direct" value="{$direct|escape:'htmlall':'UTF-8'}">
                <input type="hidden" name="gateway" value="{$gateway|escape:'htmlall':'UTF-8'}"/>
                <input type="hidden" name="payload" value=""/>
                <input type="hidden" name="tokenize" value="0"/>
                <div class="multisafepay-modal-content">
                    <div class="multisafepay-modal-header">
                        <div class="icon-title">
                            <img class="multisafepay-modal-icon" src='{$this_path_ssl|escape:'htmlall':'UTF-8'}logo.png' alt="">
                        </div>
                        <button type="button" class="close" onclick="hidePaymentModal('{$gateway|escape:'htmlall':'UTF-8'}')">
                            &#x2715;
                        </button>
                    </div>
                    <div class="multisafepay-modal-body">
                        <p id="multisafepay-{$gateway|escape:'htmlall':'UTF-8'}-notification" class="multisafepay-modal-notification" style="display: none;">
                        </p>
                        {if isset($isComponent) && $isComponent}
                            {hook h='MspPaymentComponent' mod='multisafepay' gateway=$gateway useTokenization=$useTokenization}
                            <input type="text" name="component" value="true" hidden>
                        {elseif isset($useTokenization) && $useTokenization}
                            <div class="multisafepay-token-container">
                                <div class="multisafepay-tokens">
                                    {if $multisafepay_tokens}
                                        {$removeTokenLink = {$link->getModuleLink('multisafepay', 'removetoken', [], true)|escape:'htmlall':'UTF-8'}}
                                        {foreach $multisafepay_tokens as $multisafepay_token}
                                            <label id="token-{$multisafepay_token->token|escape:'htmlall':'UTF-8'}">
                                                <input class="multisafepay-radio" type="radio" name="token" value="{$multisafepay_token->token|escape:'htmlall':'UTF-8'}" onchange="dontShowSaveNewCheckbox('{$gateway|escape:'htmlall':'UTF-8'}')" >
                                                {$multisafepay_token->display|escape:'htmlall':'UTF-8'} <button class="multisafepay-delete-token" type="button" onclick="deleteToken('{$multisafepay_token->token|escape:'htmlall':'UTF-8'}', '{$gateway|escape:'htmlall':'UTF-8'}','{$removeTokenLink|escape:'htmlall':'UTF-8'}')">
                                                    <i class="icon-trash" title="{l s='Delete saved payment details' mod='multisafepay'}"></i></button>
                                            </label>
                                        {/foreach}
                                        <label>
                                            <input class="multisafepay-radio" id="multisafepay-new-details" type="radio" name="token" value="" onchange="showSaveNewCheckbox('{$gateway|escape:'htmlall':'UTF-8'}')">
                                            {l s='Use new payment details' mod='multisafepay'}
                                        </label>
                                    {/if}
                                </div>
                                <div id='save-details-{$gateway|escape:'htmlall':'UTF-8'}' {if $multisafepay_tokens}style="display:none"{/if}>
                                    <label>
                                        <input class="multisafepay-radio" type="checkbox" name="save_details">{l s='Save my payment details' mod='multisafepay'}
                                    </label>
                                </div>
                            </div>
                        {else}
                            <div class="multisafepay-modal-fields">
                                {foreach from=$fields item=field}
                                    {if $field.type === "text"}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label mod='multisafepay'}</p>
                                            <input type="text" name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}"
                                                   placeholder="{$field.placeholder|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}"/>
                                        </label>
                                    {/if}
                                    {if $field.type === "date"}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</p>
                                            <input type="date" name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}"/>
                                        </label>
                                    {/if}
                                    {if $field.type === "email"}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</p>
                                            <input type="email" name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}"
                                                   placeholder="{$field.placeholder|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}"/>
                                        </label>
                                    {/if}
                                    {if $field.type === 'tel'}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</p>
                                            <input type="tel" name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}"
                                                   placeholder="{$field.placeholder|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}"/>
                                        </label>
                                    {/if}
                                    {if $field.type === 'select'}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</p>
                                            <select name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}">
                                                {foreach from=$field.options item=option}
                                                    <option value="{$option.value|escape:'htmlall':'UTF-8'}">{$option.name|escape:'htmlall':'UTF-8'}</option>
                                                {/foreach}
                                            </select>
                                        </label>
                                    {/if}
                                    {if $field.type === 'issuers'}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}">
                                            <p class="{if $field.required}required{/if}">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</p>
                                            <select name="{$field.name|escape:'htmlall':'UTF-8'}" class="{if $field.select2}select2{/if}" required="{$field.required|escape:'htmlall':'UTF-8'}">
                                                {foreach from=$field.options item=option}
                                                    <option value="{$option->code|escape:'htmlall':'UTF-8'}">{$option->description|escape:'htmlall':'UTF-8'}</option>
                                                {/foreach}
                                            </select>
                                        </label>
                                    {/if}
                                    {if $field.type === 'checkbox'}
                                        <label for="{$field.name|escape:'htmlall':'UTF-8'}" class="multisafepay-terms-checkbox">
                                            <input type="checkbox" name="{$field.name|escape:'htmlall':'UTF-8'}" required="{$field.required|escape:'htmlall':'UTF-8'}">
                                            <a href="{$field.link|escape:'htmlall':'UTF-8'}" target="_blank">{l s=$field.label|escape:'htmlall':'UTF-8' mod='multisafepay'}</a>
                                        </label>
                                    {/if}
                                    {if $field.type === 'hidden'}
                                        <input type="text" hidden name="{$field.name|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}"/>
                                    {/if}
                                {/foreach}
                            </div>
                        {/if}
                    </div>
                    <div class="multisafepay-modal-footer">
                        <button type="button" class="multisafepay-cancel-button"
                                onclick="hidePaymentModal('{$gateway|escape:'htmlall':'UTF-8'}')">{l s='Cancel' mod='multisafepay'}</button>
                        {if isset($isComponent) && $isComponent}
                            {$moduleLink = {$link->getModuleLink({$moduleLink|escape:'htmlall':'UTF-8'}, 'validation', [], true)|escape:'htmlall':'UTF-8'}}
                            <button type="button" onclick="submitPaymentComponent('{$moduleLink|escape:'htmlall':'UTF-8'}', '{$gateway|escape:'htmlall':'UTF-8'}')" class="btn multisafepay-pay-button" id="componentPayButton"
                            >{l s='Pay with' mod='multisafepay'} {l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}</button>
                        {else}
                            <button type="submit" class="btn multisafepay-pay-button" id="payButton"
                            >{l s='Pay with' mod='multisafepay'} {l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}</button>
                        {/if}
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>