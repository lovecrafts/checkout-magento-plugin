if (typeof  CheckoutKit != 'undefined') {
    CheckoutKit.configure(window.CKOConfigKit);
}

function checkoutApiKitPayNow() {
    var cardNumberValid     = Validation.validate($$('.cardNumber')[0]);
    var chNameValid         = Validation.validate($$('.chName')[0]);
    var expiryMonthValid    = Validation.validate($$('.expiryMonth')[0]);
    var expiryYearValid     = Validation.validate($$('.expiryYear')[0]);
    var chCvvValid          = Validation.validate($$('.chCvv')[0]);


    if (!cardNumberValid || !chNameValid || !expiryMonthValid || !expiryYearValid || !chCvvValid) {
        return false;
    }

    CheckoutKit.createCardToken({
            number:         $$('.cardNumber')[0].value,
            name:           $$('.chName')[0].value,
            expiryMonth:    $$('.expiryMonth')[0].value,
            expiryYear:     $$('.expiryYear')[0].value,
            cvv:            $$('.chCvv')[0].value
        }, function(response){
            if (response.type === 'error') {
                alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                return;
            }

            if (response.id) {
                $('cko-kit-card-token').value = response.id;
                $$('.validate-kit-token')[0].value = 'true';
                Validation.validate($$('.validate-kit-token')[0]);

                $('checkout-kit-pay-button').addClassName('disabled');
                $('checkout-kit-pay-button').disable();

                if (typeof payment != 'undefined' && typeof payment.save == 'function') {
                    payment.save();
                }
            } else {
                alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                return;
            }
        }
    );
}