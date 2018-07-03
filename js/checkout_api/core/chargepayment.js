var checkoutApi = Class.create();
checkoutApi.prototype = {
    initialize : function(methodCode, controller, saveOrderUrl, baseSaveOrderUrl) {
        this.code               = methodCode;
        this.controller         = controller;
        this.saveOrderUrl       = saveOrderUrl;
        this.baseSaveOrderUrl   = baseSaveOrderUrl;
        this.phpMethodCode      = 'checkoutapicard';
        this.jsMethodCode       = 'checkoutapijs';
        this.kitMethodCode      = 'checkoutapikit';
        this.hostedMethodCode   = 'hosted';
        this.framesMethodCode   = 'checkoutapiframes';
        this.preparePayment();
    },
    preparePayment: function() { 
        switch (this.controller) {
            case 'onepage':
                this.prepareSubmit();
                break;
            case 'sales_order_create':
            case 'sales_order_edit':
                this.prepareAdminSubmit();
                break;
        }
    },
    prepareSubmit: function() {
        var button = $('review-buttons-container').down('button');
        button.writeAttribute('onclick', '');
        button.stopObserving('click');
        switch (this.code) {
            case this.phpMethodCode:
                button.observe('click', function() {
                    this.saveOrderSubmit();
                }.bind(this));
                break;
            case this.jsMethodCode:
                button.observe('click', function() {
                    this.checkoutApiFrame();
                }.bind(this));
                break;
            case this.kitMethodCode:
                button.observe('click', function() {
                    this.checkoutKit();
                }.bind(this));
                break;
            case this.framesMethodCode:
                button.observe('click', function() {
                    this.checkoutFrames();
                }.bind(this));
                break;
        }
    },
    prepareAdminSubmit: function() {
        var paymentMethods = $('edit_form').getInputs('radio','payment[method]');
        for ( var i = 0; i < paymentMethods.length; i++) {
            paymentMethods[i].observe('click', function() {
                this.changeAdminOrderForm();
            }.bind(this));
        }

        this.changeAdminOrderForm();
    },
    getPaymentMethodChecked: function() {
        var paymentMethodChecked = $('edit_form').getInputs('radio','payment[method]').find(function(radio) {
            return radio.checked;
        });

        return paymentMethodChecked;
    },
    changeAdminOrderForm: function() {
        var paymentMethodChecked = this.getPaymentMethodChecked();

        if (typeof paymentMethodChecked === 'undefined') {
            return;
        }

        if (paymentMethodChecked.value == this.code) {
            $('edit_form').writeAttribute('action', this.saveOrderUrl);

            if (typeof directPostModel !== 'undefined') {
                directPostModel.nativeAction = this.saveOrderUrl;
            }
        } else {
            $('edit_form').writeAttribute('action', this.baseSaveOrderUrl);

            if (typeof directPostModel !== 'undefined') {
                directPostModel.nativeAction = this.baseSaveOrderUrl;
            }
        }
    },
    checkoutApiFrame: function() {
        if (this.agreementIsValid()) {
            if($('checkoutapi-new-card')){
                if($('checkoutapi-new-card').checked){
                    CKOAPIJS.open();
                    if (CKOAPIJS.isMobile()) {
                        $('checkout-api-js-hover').show();
                    }
                } else {
                    this.saveOrderSubmit();
                }
            } else {
                CKOAPIJS.open();
                if (CKOAPIJS.isMobile()) {
                    $('checkout-api-js-hover').show();
                }
            }
        } else {
            alert('Please agree to all the terms and conditions before placing the order.');
            return;
        }
    },
    checkoutKit: function() {
        var self = this;

        var createCToken = ( function () {
            CheckoutKit.createCardToken({
                    number: $$('.cardNumber')[0].value,
                    name : $$('.chName')[0].value,
                    expiryMonth: $$('.expiryMonth')[0].value,
                    expiryYear: $$('.expiryYear')[0].value,
                    cvv: $$('.cvv')[0].value
                }, function(response){
                    if (response.type === 'error') {
                        alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                        return;
                    }

                    if (response.id) {
                        $('cko-kit-card-token').value = response.id;

                        self.saveOrderSubmit();
                    } else {
                        alert('Your payment was not completed. Please check you card details and try again or contact customer support.');
                        return;
                    }
                }
            );
        });
        
        if (this.agreementIsValid()) {
            CheckoutKit.configure(window.CKOConfigKit);

            if(jQuery('#checkoutapi-new-card').length > 0){
                if(jQuery('#checkoutapi-new-card').prop("checked")== true ){
                    createCToken();
                } else{
                    self.saveOrderSubmit();
                }
            } else{
                createCToken();
            }

        } else {
            alert('Please agree to all the terms and conditions before placing the order.');
            return;
        }
    },
    agreementIsValid: function() {
        var isValid = true;

        $$('.checkout-agreements input[type="checkbox"]').each(
            function(Element) {
                if (!Element.checked) {
                    isValid = false;
                }
            }
        );

        return isValid;
    },
    saveOrderSubmit: function() {
        if (this.agreementIsValid()) {
            review.save();
        } else {
            alert('Please agree to all the terms and conditions before placing the order.');
            return;
        }
    },
    checkoutFrames: function(){
        if (this.agreementIsValid()) {
            if($('checkoutapiframes-new-card')){
                if($('checkoutapiframes-new-card').checked){
                    if (Frames.isCardValid()) Frames.submitCard();
                } else {
                    if(jQuery('#alt-payment:checked').length > 0){
                        
                            if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-ideal"){
                                var modal = document.getElementById('ckoModal');
                                modal.style.display = "block";

                                jQuery('#idealInfo').show();
                                jQuery('#boletoInfo').hide();
                                jQuery('#qiwiInfo').hide();

                            } else if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-boleto"){
                                var modal = document.getElementById('ckoModal');
                                modal.style.display = "block";

                                jQuery('#idealInfo').hide();
                                jQuery('#boletoInfo').show();
                                jQuery('#qiwiInfo').hide();

                            } else if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-qiwi"){
                                var modal = document.getElementById('ckoModal');
                                modal.style.display = "block";

                                jQuery('#qiwiInfo').show();
                                jQuery('#boletoInfo').hide();
                                jQuery('#idealInfo').hide();
                            } else {
                                window.checkoutApiSubmitOrder();
                            }

                        jQuery('#mybtn').on('click', function(e) {
                            if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-ideal"){
                                var e = document.getElementById("issuer");
                                var value = e.options[e.selectedIndex].value;
                                var text = e.options[e.selectedIndex].text;

                                document.getElementById('cko-lp-issuerId').value = value;
                            } else if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-boleto"){
                                if(document.getElementById('boletoDate').value == ""){
                                    alert('Please enter correct date');
                                    return false;
                                } else {
                                    document.getElementById('cko-lp-boletoDate').value = document.getElementById('boletoDate').value;
                                }

                                if(document.getElementById('cpf').value == ""){
                                    alert('Please enter your CPF');
                                    return false;
                                } else {
                                    document.getElementById('cko-lp-cpf').value = document.getElementById('cpf').value;
                                }

                                if(document.getElementById('custName').value == ""){
                                    alert('Please enter your customer name');
                                    return false;
                                } else {
                                    document.getElementById('cko-lp-custName').value = document.getElementById('custName').value;
                                }

                            } if(jQuery('#alt-payment:checked').val() == "checkoutapiframes-apm-qiwi"){
                                if(document.getElementById('walletId').value == ""){
                                    alert('Please enter your Wallet Id');
                                    return false;
                                } else {
                                    document.getElementById('cko-lp-walletId').value = document.getElementById('walletId').value;
                                }
                            }

                            modal.style.display = "none";
                            window.checkoutApiSubmitOrder();

                        });
                    } else {
                        this.saveOrderSubmit();
                    }
                }
            } else {
                if (Frames.isCardValid()) Frames.submitCard();
            }
        } else {
            alert('Please agree to all the terms and conditions before placing the order.');
            return;
        }

    }
};