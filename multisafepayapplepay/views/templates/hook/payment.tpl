{include file="$main_path_ssl/modules/multisafepay/views/templates/hook/payment.tpl"}

<script>
    var applePayPaymentOptionsBlock = document.getElementById('APPLEPAY');
    applePayPaymentOptionsBlock.style.display = 'none';

    try {
        if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
            applePayPaymentOptionsBlock.style.display = 'block';
        }
    } catch (error) {
        console.warn('MultiSafepay error when trying to initialize Apple Pay:', error);
    }
</script>
