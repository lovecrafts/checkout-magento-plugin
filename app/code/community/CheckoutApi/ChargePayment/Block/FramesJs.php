<?php
/**
 * Frame for js API
 *
 * Class CheckoutApi_ChargePayment_Block_Frame
 *
 * @version 20160203
 */
class CheckoutApi_ChargePayment_Block_FramesJs  extends Mage_Core_Block_Template
{
    /**
     * Return TRUE if is JS API
     *
     * @return bool
     *
     * @version 20160203
     */
    public function isFramesPaymentMethod() {
       $paymentMethod = (string)Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod();

        return $paymentMethod === CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_FRAMES ? true : false;
    }

    /**
     * Return Payment Code
     *
     * @return string
     *
     * @version 20160219
     */
    public function getPaymentCode() {
        return CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_FRAMES;
    }

    public function getLocalPaymentInformation(){ 
        $secretKey =  Mage::getModel('chargepayment/creditCardFrames')->getSecretKey();
        $mode = Mage::getModel('chargepayment/creditCardFrames')->getMode();
        $lpId = 'lpp_9';
        $url = "https://sandbox.checkout.com/api2/v2/lookups/localpayments/{$lpId}/tags/issuerid";
        
        if($mode == 'live'){
            $url = "https://api2.checkout.com/v2/lookups/localpayments/{$lpId}/tags/issuerid";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER=>  false,
            CURLOPT_HTTPHEADER => array(
              "authorization: ".$secretKey,
              "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
          WC_Checkout_Non_Pci::log("cURL Error #:" . $err);
        } else {

            $test = json_decode($response);

            foreach ((array)$test as &$value) { 
                foreach ($value as $i=>$item){
                    foreach ($item as  $is=>$items) {
                        return $item->values;
                    }
                }
            }
        }
    }
}