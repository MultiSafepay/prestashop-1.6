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
{capture name=path}{l s='Payment' mod='multisafepay'}{/capture}

<h2>{l s='Order summary' mod='multisafepay'}</h2><br/>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($errors) AND $errors}
    {include file="$tpl_dir./errors.tpl"}
{/if}

{$logo_url = "{$this_path_ssl|escape:'htmlall':'UTF-8'}logo.png"}

{if strpos($moduleLink, "genericgateway") !== false}
    {$logo_url = "/img/multisafepay/{$moduleLink}-logo.png"}
{/if}


<h3>{l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}</h3>

<form action="{$link->getModuleLink({$moduleLink|escape:'htmlall':'UTF-8'}, 'validation', [], true)|escape:'htmlall':'UTF-8'}"
      onsubmit="var submit = this.querySelector('input[type=submit]'); submit.value = '{l s='Processing...' mod='multisafepay' }'; submit.disabled = true;"
      method="post" id="multisafepay-form-{$gateway|escape:'htmlall':'UTF-8'|lower}">
    <input type="hidden" name="gateway" value="{$gateway|escape:'htmlall':'UTF-8'}"/>
    <p>
        <img src="{$logo_url|escape:'htmlall':'UTF-8'}" alt="{l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}" style="float:left; margin: 0px 10px 5px 0px;" />
        <br/>
        {l s='You have choosen to pay by ' mod='multisafepay'} {l s={$name|escape:'quotes':'UTF-8'} mod='multisafepay'}
        <br/>
        {l s='The total amount of your order is ' mod='multisafepay'}
        <span id="amount" class="price">{convertPrice price="{$total}"}</span>
        <br/><br/><br/><br/>
    </p>

    {if (in_array ($gateway, array ('AMEX', 'VISA', 'MAESTRO', 'MASTERCARD'))) }
        {hook h='MspTokenization' mod='multisafepay'}
    {/if}
        
    {if (in_array ($gateway, array ('CREDITCARD'))) }
        {hook h='MspTokenization' mod='multisafepay' msp_display_all_cards='1'}
    {/if}

    {if $gateway === 'CREDITCARD'}
        {hook h='MspPaymentComponent' mod='multisafepay' gateway=$gateway}
    {/if}
    
    <p class="cart_navigation" id="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button_large">{l s='Change payment method' mod='multisafepay'}</a>

        {if isset($errors) AND $errors}
            <input type="submit" disabled value="{l s='Confirm order' mod='multisafepay'}" class="button_large" disabled />
        {else}
            <input type="submit" value="{l s='Confirm order' mod='multisafepay'}" class="button_large" />
        {/if}
     </p>

</form>
