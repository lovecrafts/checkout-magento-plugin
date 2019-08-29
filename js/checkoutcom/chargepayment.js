jQuery(document).ready(function() {
    /**
     *This function will get the base URL of the domain it's loaded from.
     *In case the url contains index.php load base URL as complex path, for example
     *if the base is https://test.com/magento/index.php/onepage the function will return
     *the url in the following form -> "https://test.com/magento" instead of "https://test.com/"
     *
     * @returns The base URL of the domain
     */
    getBaseUrl = function() {
        var fullUrl = window.location.href;
        return fullUrl.indexOf("index.php") !== -1 ?
            window.location.href.substr(0, window.location.href.indexOf('index.php') - 1) :
            window.location.protocol + window.location.host
    }

    // hide div on page load
    if(jQuery('#p_method_checkoutcomcards').length > 0){
        jQuery('#cko-container').hide();
        jQuery('#payment_form_checkoutcomcards').hide();

        // show div if checkoutcom is checked
        if($('p_method_checkoutcomcards').checked){
            jQuery('#cko-container').show();
            jQuery('#payment_form_checkoutcomcards').show();
        }
    }
});