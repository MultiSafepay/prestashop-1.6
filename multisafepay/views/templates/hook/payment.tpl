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
<div class="row">
    <div class="col-xs-12">
        {assign var='directMethods' value=['BIZUM', 'IDEAL', 'IN3']}
        {if $gateway|in_array:$directMethods || (!$direct && !$useTokenization)}
            <form
                    id="{$gateway|escape:'htmlall':'UTF-8'}"
                    action="{$link->getModuleLink({$moduleLink|escape:'htmlall':'UTF-8'}, 'validation', [], true)|escape:'htmlall':'UTF-8'}"
                    method="post"
                    class="payment-module-form"
            >
                <input type="hidden" name="gateway" value="{$gateway|escape:'htmlall':'UTF-8'}"/>
                <p class="payment_module">
                    <a
                            class="multisafepay-payment-module"
                            style="background-image: url('{$this_path_ssl|escape:'htmlall':'UTF-8'}logo.png')"
                            href="{$link->getModuleLink({$moduleLink|escape:'htmlall':'UTF-8'}, 'validation', ['gateway'    =>"{$gateway|default:''}"] , true)|escape:'htmlall':'UTF-8'}"
                            rel="nofollow"
                    >
                        {l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}
                        <span>
                            {if isset($fee['increment']) && $fee['increment']}
                                {l s='( + ' mod='multisafepay'}
                                {displayPrice price=$fee['increment']|escape:'htmlall':'UTF-8'}
                                {l s=' )' mod='multisafepay'}
                            {/if}

                            {if isset($fee['multiply']) && $fee['multiply']}
                                {l s='( + ' mod='multisafepay'}
                                {$fee['multiply']|escape:'htmlall':'UTF-8'|round:'2'}%
                                {l s=' )' mod='multisafepay'}
                            {/if}
                        </span>
                    </a>
                </p>
            </form>
        {else}
            <p class="payment_module">
                <a
                        class="multisafepay-payment-module"
                        onclick="showPaymentModal('{$gateway|escape:'htmlall':'UTF-8'}')"
                        style="background-image: url('{$this_path_ssl|escape:'htmlall':'UTF-8'}logo.png')"
                >
                    {l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}
                    <span>
                        {if isset($fee['increment']) && $fee['increment']}
                            {l s='( + ' mod='multisafepay'}
                            {displayPrice price=$fee['increment']|escape:'htmlall':'UTF-8'}
                            {l s=' )' mod='multisafepay'}
                        {/if}

                        {if isset($fee['multiply']) && $fee['multiply']}
                            {l s='( + ' mod='multisafepay'}
                            {$fee['multiply']|escape:'htmlall':'UTF-8'|round:'2'}%
                            {l s=' )' mod='multisafepay'}
                        {/if}
                    </span>
                </a>
            </p>
        {/if}
    </div>
</div>

{if !($gateway|in_array:$directMethods) && ($direct || $useTokenization)}
    {include file="$main_path_ssl/modules/multisafepay/views/templates/hook/modal.tpl"}
{/if}