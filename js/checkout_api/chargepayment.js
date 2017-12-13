document.observe('dom:loaded', function(){ console.log('dom:loaded');
    if (!window.hasOwnProperty('jsCheckoutApi')) {
        return false;
    }
    
    var method = window.jsCheckoutApi.method;

    switch (method) {
        case 'checkoutapijs':
            checkoutJs();
            break;
        case 'checkoutapikit':
            checkoutKit();
            break;
        case 'checkoutapiembedded':
            checkoutEmbedded();
            break;
    }

    function checkoutJs() {

        if(jQuery('#onestepcheckout-form').find('.onestepcheckout-error').length){
            var url = window.location.href+'ajax/set_methods_separate/';
            var update_payments = true;
            get_separate_save_methods_function(url, update_payments)();
        }

        var controllerName = window.jsCheckoutApi.controllerName;
        
        if(controllerName == 'index'){
            //Hide default OPC Place order button
            jQuery('.onestepcheckout-place-order a').hide();

            //Create Checkoutapipayment button
            var btn = document.createElement('button');
            var txt = document.createTextNode('Place order now');

            btn.appendChild(txt);
            btn.setAttribute('type', 'button');
            btn.setAttribute('id', 'btncheckoutapipayment');
            btn.setAttribute('class','large orange onestepcheckout-button');
            
            jQuery(".onestepcheckout-place-order").append(btn);
            jQuery('#btncheckoutapipayment').hide();

            if(jQuery('#p_method_checkoutapijs').length > 0){
                if($( "p_method_checkoutapijs" ).checked){
                    jQuery('#btncheckoutapipayment').show();
                    jQuery('.onestepcheckout-place-order a').hide();

                    jQuery('.payment-methods input:radio').change(function() {
                       if($( "p_method_checkoutapijs" ).checked){
                                jQuery('#btncheckoutapipayment').show();
                                jQuery('.onestepcheckout-place-order a').hide();    

                            } else {
                                jQuery('#btncheckoutapipayment').hide();
                                jQuery('.onestepcheckout-place-order a').show();
                        }
                    });
                } else{
                    
                    jQuery('.onestepcheckout-place-order a').show();
                    jQuery('.payment-methods input:radio').change(function() {
                       if($( "p_method_checkoutapijs" ).checked){
                                jQuery('#btncheckoutapipayment').show();
                                jQuery('.onestepcheckout-place-order a').hide();    

                        } else {
                            jQuery('#btncheckoutapipayment').hide();
                            jQuery('.onestepcheckout-place-order a').show();
                        }
                    });

                }

                $('btncheckoutapipayment').observe('click', function(e){
                    Event.stop(e);
                    var already_placing_order = false;

                   // First validate the form
                    var form = new VarienForm('onestepcheckout-form');

                    if(!form.validator.validate())  {
                        Event.stop(e);
                    }
                    else    {

                        if($('checkoutapi-new-card')){
                            if($('checkoutapi-new-card').checked){
                                CKOAPIJS.open();
                                if (CKOAPIJS.isMobile()) { 
                                    $('checkout-api-js-hover').show();
                                }
                            } else {
                                window.checkoutApiSubmitOrder();
                            }
                        } else {
                            CKOAPIJS.open();
                            if (CKOAPIJS.isMobile()) {
                                $('checkout-api-js-hover').show();
                            }
                        }
                        
                    }
                });
            }
        }
    }


    function checkoutEmbedded(){ 
        var controllerName = window.jsCheckoutApi.controllerName;
        
        if(controllerName == 'index'){
            //Hide default OPC Place order button
            //jQuery('.onestepcheckout-place-order a').hide();

            //Create Checkoutapipayment button
            var btn = document.createElement('button');
            var txt = document.createTextNode('Place order now checkout');

            btn.appendChild(txt);
            btn.setAttribute('type', 'button');
            btn.setAttribute('id', 'btncheckoutapipayment');
            btn.setAttribute('class','large orange onestepcheckout-button');
            
            jQuery(".onestepcheckout-place-order").append(btn);
            jQuery('#btncheckoutapipayment').hide();

            if(jQuery('#p_method_checkoutapiembedded').length > 0){
                if($( "p_method_checkoutapiembedded" ).checked){
                    jQuery('#btncheckoutapipayment').show();
                    jQuery('.onestepcheckout-place-order a').hide();

                    jQuery('.payment-methods input:radio').change(function() {
                       if($( "p_method_checkoutapiembedded" ).checked){
                                jQuery('#btncheckoutapipayment').show();
                                jQuery('.onestepcheckout-place-order a').hide();    

                            } else {
                                jQuery('#btncheckoutapipayment').hide();
                                jQuery('.onestepcheckout-place-order a').show();
                        }
                    });
                } else{
                    jQuery('.onestepcheckout-place-order a').show();
                    jQuery('.payment-methods input:radio').change(function() {
                       if($( "p_method_checkoutapiembedded" ).checked){
                                jQuery('#btncheckoutapipayment').show();
                                jQuery('.onestepcheckout-place-order a').hide();    

                        } else {
                            jQuery('#btncheckoutapipayment').hide();
                            jQuery('.onestepcheckout-place-order a').show();
                        }
                    });
                }

                if(jQuery('#btncheckoutapipayment').length > 0){
                    $('btncheckoutapipayment').observe('click', function(e){
                        Event.stop(e);
                        var already_placing_order = false;

                       // First validate the form
                        var form = new VarienForm('onestepcheckout-form');

                        if(!form.validator.validate())  {
                            Event.stop(e);
                        }
                        else{
                           if($('checkoutapiembedded-new-card')){ 
                                if($('checkoutapiembedded-new-card').checked){
                                    if (Frames.isCardValid()) Frames.submitCard();
                                } else {
                                    window.checkoutApiSubmitOrder();
                                }
                            } else {
                                 if (Frames.isCardValid()) Frames.submitCard();
                            }
                            
                        }
                    });
                }
            }
        }
    }

    function checkoutKit() {
        $('checkout-api-js-hover').show();

        setTimeout(function(){
            if (CheckoutKit !== undefined) {
                CheckoutKit.configure(window.CKOConfigKit);

                $$('.cardNumber')[0].value  = window.jsCheckoutApi.kit_number;
                $$('.chName')[0].value      = window.jsCheckoutApi.kit_name;
                $$('.expiryMonth')[0].value = window.jsCheckoutApi.kit_month;
                $$('.expiryYear')[0].value  = window.jsCheckoutApi.kit_year;
                $$('.chCvv')[0].value       = window.jsCheckoutApi.kit_cvv;

                CheckoutKit.createCardToken({
                        number:         window.jsCheckoutApi.kit_number,
                        name:           window.jsCheckoutApi.kit_name,
                        expiryMonth:    window.jsCheckoutApi.kit_month,
                        expiryYear:     window.jsCheckoutApi.kit_year,
                        cvv:            window.jsCheckoutApi.kit_cvv
                    }, function(response){
                        if (response.type === 'error') {
                            alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                            $('checkout-api-js-hover').hide();
                            return;
                        }

                        if (response.id) {
                            $('cko-kit-card-token').value = response.id;

                            window.checkoutApiSubmitOrder();

                            $('checkout-api-default-hover').hide();
                            $('checkout-api-js-hover').hide();
                        } else {
                            alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                            $('checkout-api-js-hover').hide();
                            return;
                        }
                    }
                );
            }
        }, 2000);
    }
});

window.checkoutApiSubmitOrder = function() { 
    if  (typeof window.checkoutApiSubmitOrderCustom != 'undefined') {
        window.checkoutApiSubmitOrderCustom();

        return true;
    }

    if ($('aw-onestepcheckout-place-order-button') !== null) {
        $('aw-onestepcheckout-place-order-button').click();
    }

    if ($('onestepcheckout-button-place-order') !== null) {
        $('onestepcheckout-button-place-order').click();
    }

    if ($('onestepcheckout-place-order') !== null) {
        $('onestepcheckout-place-order').click();
    }

    if (typeof review !== 'undefined' && review) {
        review.save();
    }
}