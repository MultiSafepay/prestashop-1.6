{*
* MultiSafepay Payment Module
*
*  @author MultiSafepay <integration@multisafepay.com>
*  @copyright  Copyright (c) 2015 MultiSafepay (http://www.multisafepay.com)
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<form id="multisafepay_fco_payment_form" action="{$base_dir_ssl|escape:'htmlall':'UTF-8'}index.php?fc=module&module=multisafepayfco&controller=validation" title="{l s='MultiSafepay FastCheckout' mod='multisafepayfco'}" method="post" data-ajax="false">
    {if isset($smarty.get.id_product)}<input type="hidden" name="id_product" value="{$smarty.get.id_product|intval}" />{/if}
    <!-- Change dynamicaly when the form is submitted -->
    <input type="hidden" name="quantity" value="1" />
    <input type="hidden" name="id_p_attr" value="" />
    <INPUT TYPE="image" SRC="{$img|escape:'htmlall':'UTF-8'}"   BORDER="0" ALT="Fast Checkout!" style="float:right;margin-bottom:20px;"> 
</form>
<div class="clear" style="margin-bottom:20px;">&nbsp;</div>

