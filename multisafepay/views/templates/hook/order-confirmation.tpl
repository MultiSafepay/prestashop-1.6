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
<p><h2>{l s='Thank you for your order' mod='multisafepay' }</h2></p><br/>
<p>{l s='Your order #%s on %s is complete' sprintf=[{$order->id|escape:'htmlall':'UTF-8'}, {$shop_name|escape:'htmlall':'UTF-8'} ] mod='multisafepay'}</p>
<p>{l s='A confirmation e-mail is send to %s ' sprintf=[{$cookie->email|escape:'htmlall':'UTF-8'}] mod='multisafepay'}</p>
<br/>


<table class="std">
    <thead>
        <tr>
            <th>{l s='Product' mod='multisafepay'}</th>
            <th>{l s='Price' mod='multisafepay'}</th>
            <th>{l s='Qty' mod='multisafepay'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$order_products item=product}
            <tr>
                <td>{$product.product_name|escape:'htmlall':'UTF-8'}</td>
                <td>
                    {if $use_taxes}
                        {displayPrice price=$product.total_price_tax_incl}
                    {else}
                        {displayPrice price=$product.total_price_tax_excl}
                    {/if}
                </td>
                <td>{$product.product_quantity|escape:'htmlall':'UTF-8'}</td>
            </tr>
        {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <td style="text-align:right">
                {l s='Products Total' mod='multisafepay'}
            </td>
            <td colspan="2">
                {if $use_taxes}
                    {displayPrice price=$order->total_products_wt}
                {else}
                    {displayPrice price=$order->total_products}
                {/if}
                 
            </td>
        </tr> 
        <tr>
            <td style="text-align:right">
                {l s='Shipping' mod='multisafepay'}
            </td>
            <td colspan="2">
                {if $use_taxes}
                    {displayPrice price=$order->total_shipping_tax_incl}
                {else}
                    {displayPrice price=$order->total_shipping_tax_excl}
                {/if}
                 
            </td>
        </tr>
        {if $order->total_discounts != '0.00'}
            <tr>
                <td style="text-align:right">
                    {l s='Discounts' mod='multisafepay'}
                </td>
                <td colspan="2">-
                    {if $use_taxes}
                        {displayPrice price=$order->total_discounts_tax_incl}
                    {else}
                        {displayPrice price=$order->total_discounts_tax_excl}
                    {/if}
                </td>
            </tr>
        {/if}
        {if $use_taxes}
            <tr>
                <td style="text-align:right">
                    {l s='Taxes Paid' mod='multisafepay'}
                </td>
                <td colspan="2">
                    {$taxamt = $order->total_paid_tax_incl - $order->total_paid_tax_excl}
                    {displayPrice price=$taxamt}
 
                </td>
            </tr>
        {/if}
        <tr>
            <td style="text-align:right">
                {l s='TOTAL' mod='multisafepay'}
            </td>
            <td colspan="2">
                {if $use_taxes}
                    {displayPrice price=$order->total_paid_tax_incl}
                {else}
                    {displayPrice price=$order->total_paid_tax_excl}
                {/if}
            </td>
        </tr>
    </tfoot>
</table>



</p>