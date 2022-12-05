window.addEventListener('load', () => {
    const googlePayClient = new google.payments.api.PaymentsClient({
        environment: 'PRODUCTION'
    })

    const baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedCardNetworks: ['VISA', 'MASTERCARD'],
            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS']
        }
    }

    const googlePayBaseConfiguration = {
        apiVersion: 2,
        apiVersionMinor: 0,
        allowedPaymentMethods: [baseCardPaymentMethod],
        existingPaymentMethodRequired: true
    }

    const googlePayBlock = document.getElementById('GOOGLEPAY')
    googlePayClient.isReadyToPay(googlePayBaseConfiguration)
        .then((res) => {
            if (!res.result) {
                googlePayBlock.hidden = true
            }
        })
        .catch((error) => {
            googlePayBlock.hidden = true
            console.error(error)
        })
})