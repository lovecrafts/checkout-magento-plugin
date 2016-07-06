if ($('co-payment-form') !== null) {
    var methods = $$('input[name=payment[method]]');

    if (methods.length > 0) {
        $('co-payment-form').on('change', 'input:radio', function(el) {
            if ($(el.target).value === 'checkoutapijs' && typeof CKOAPIJS != 'undefined') {
                CKOAPIJS.open();
            }
        });
    }
}