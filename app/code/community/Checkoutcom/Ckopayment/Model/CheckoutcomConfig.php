<?php

/**
 * Class Checkoutcom_Ckopayment_Model_CheckoutcomConfig
 */

class Checkoutcom_Ckopayment_Model_CheckoutcomConfig
{
    // Identifiers for the payment options
    const CONFIGURATION = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_CONFIG;
    const CARD = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_CARDS;
    const ALTERNATIVE = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_APMS;
    const APPLEPAY = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_APPLEPAY;
    const GOOGLEPAY = Checkoutcom_Ckopayment_Helper_Data::CODE_CHECKOUT_COM_GOOGLEPAY;

    // Magic strings used to get to the admin configuration options
    const RootName = 'ckopayment';
    const Environment = "ckocom_environment";
    const SecretKey = "ckocom_sk";
    const PublicKey = "ckocom_pk";
    const PrivateSharedKey = "ckocom_psk";
    const PaymentAction = "ckocom_card_autocap";
    const CaptureDelay = "ckocom_card_delay";
    const AuthorisedOrderStatus = "ckocom_order_authorised";
    const CapturedOrderStatus = "ckocom_order_captured";
    const FlaggedOrderStatus = "ckocom_order_flagged";
    const RefundedOrderStatus = "ckocom_order_refunded";
    const VoidedOrderStatus = "ckocom_order_voided";
    const CreateInvoice = "ckocom_create_invoice";
    const CreateMemo = "ckocom_credit_memo";
    const CardsTitle = "title";
    const ThreeD = "ckocom_card_threed";
    const AttemptNoThreeD = "ckocom_card_notheed";
    const SavedCards = "ckocom_card_saved";
    const SavedCardsTitle = "ckocom_card_saved_title";
    const SavedCardOption = "ckocom_card_saved_label";
    const DynamicDescriptor = "ckocom_card_desctiptor";
    const DescriptorName = "ckocom_card_desctiptor_name";
    const DescriptorCity = "ckocom_card_desctiptor_city";
    const Mada = "ckocom_card_mada";
    const AlternativePaymentsTitle = "title";
    const AppleMerchantIdentifier = "ckocom_apple_mercahnt_id";
    const AppleCertificate = "ckocom_apple_certificate";
    const AppleCertificateKey = "ckocom_apple_key";
    const AppleTitle = "title";
    const AppleButtonLocation = "ckocom_apple_location";
    const AppleButtonType = "ckocom_apple_type";
    const AppleButtonTheme = "ckocom_apple_theme";
    const AppleButtonLanguage = "ckocom_apple_language";
    const GoogleMerchantIdentifier = "ckocom_google_merchant_id";
    const GoogleTitle = "title";
    const GoogleButtonType = "ckocom_google_style";
    const ALTERNATIVE_PAYMENT_METHOD = 'ckocom_apms_selector';

    /**
     * Get Environment from admin module setting
     *
     * @return mixed
     */
    public function getEnvironment()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::Environment);
    }

    /**
     * Get Secret Key from admin module setting
     *
     * @return mixed
     */
    public function getSecretKey()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::SecretKey);
    }

    /**
     * Get Public Key from admin module setting
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::PublicKey);
    }

    /**
     * Get Private Share Key from admin module setting
     *
     * @return mixed
     */
    public function getPrivateSharedKey()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::PrivateSharedKey);
    }

    /**
     * Get Payment Action from admin module setting
     *
     * @return mixed
     */
    public function getPaymentAction()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::PaymentAction);
    }

    /**
     * Add a delay to the current URC time
     *
     * @return string ISO 8601 timestamp of UTC current time plus delays
     */
    public function getDelayedCaptureTimestamp()
    {
        // Specify a 10 seconds delay even if the autocapture time is set to 0 to avoid webhook issues
        $defaultSecondsDelay = 10;
        $delay = preg_replace('/\s/', '', Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::CaptureDelay));
        // If the input of the delay is numeric
        if (is_numeric($delay)) {
            // Get total seconds based on the hour input
            $totalSeconds = $delay * 3600;
            // If the delay is 0 manually add a 10 seconds delay
            if ($totalSeconds == 0) {
                $totalSeconds += $defaultSecondsDelay;
            }
            $hours = floor($totalSeconds / 3600);
            $minutes = floor($totalSeconds / 60 % 60);
            $seconds = floor($totalSeconds % 60);
            // Return date and time in UTC with the delays added
            return gmdate("Y-m-d\TH:i:s\Z", strtotime('+' . $hours . ' hours +' . $minutes . ' minutes +' . $seconds . 'seconds'));
        }
        // If the delay is in an invalid format (non-numeric) default to base delay (defaultSecondsDelay)
        return gmdate("Y-m-d\TH:i:s\Z", strtotime('+' . $defaultSecondsDelay . 'seconds'));
    }

    /**
     * Get Authorised Order Status from admin module setting
     *
     * @return mixed
     */
    public function getAuthorisedOrderStatus()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::AuthorisedOrderStatus);
    }

    /**
     * Get Captured Order Status from admin module setting
     *
     * @return mixed
     */
    public function getCapturedOrderStatus()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::CapturedOrderStatus);
    }

    /**
     * Get Flagged Order Status from admin module setting
     *
     * @return mixed
     */
    public function getFlaggedOrderStatus()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::FlaggedOrderStatus);
    }

    /**
     * Get Refunded Order Status from admin module setting
     *
     * @return mixed
     */
    public function getRefundedOrderStatus()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::RefundedOrderStatus);
    }

    /**
     * Get Voided Order Status from admin module setting
     *
     * @return mixed
     */
    public function getVoidedOrderStatus()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::VoidedOrderStatus);
    }

    /**
     * Get Create Invoice from admin module setting
     *
     * @return mixed
     */
    public function getCreateInvoice()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::CreateInvoice);
    }

    /**
     * Get Create Memo from admin module setting
     *
     * @return mixed
     */
    public function getCreateMemo()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CONFIGURATION, self::CreateMemo);
    }

    /**
     * Get Card Option Title from admin module setting
     *
     * @return mixed
     */
    public function getCardsTitle()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::CardsTitle);
    }

    /**
     * Get 3D option from admin module setting
     *
     * @return mixed
     */
    public function getThreeD()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::ThreeD);
    }

    /**
     * Get Attempt no 3D option from admin module setting
     *
     * @return mixed
     */
    public function getAttemptNoThreeD()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::AttemptNoThreeD);
    }

    /**
     * Get Saved Cards option from admin module setting
     *
     * @return mixed
     */
    public function getSavedCards()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::SavedCards);
    }

    /**
     * Get Saved Cards Title from admin module setting
     *
     * @return mixed
     */
    public function getSavedCardsTitle()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::SavedCardsTitle);
    }

    /**
     * Get Saved Card Option Label from admin module setting
     *
     * @return mixed
     */
    public function getSavedCardOption()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::SavedCardOption);
    }

    /**
     * Get Dynamic Descriptor option from admin module setting
     *
     * @return mixed
     */
    public function getDynamicDescriptor()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::DynamicDescriptor);
    }

    /**
     * Get Descriptor Name from admin module setting
     *
     * @return mixed
     */
    public function getDescriptorName()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::DescriptorName);
    }

    /**
     * Get Descriptor City from admin module setting
     *
     * @return mixed
     */
    public function getDescriptorCity()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::DescriptorCity);
    }

    /**
     * Get Mada option from admin module setting
     *
     * @return mixed
     */
    public function getMada()
    {
        return Mage::helper(self::RootName)->getConfigData(self::CARD, self::Mada);
    }

    /**
     * Get Alternative Payments Title from admin module setting
     *
     * @return mixed
     */
    public function getAlternativePaymentsTitle()
    {
        return Mage::helper(self::RootName)->getConfigData(self::ALTERNATIVE, self::AlternativePaymentsTitle);
    }

    /**
     * Get Apple Merchant Identifier from admin module setting
     *
     * @return mixed
     */
    public function getAppleMerchantIdentifier()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleMerchantIdentifier);
    }

    /**
     * Get Apple Certificate from admin module setting
     *
     * @return mixed
     */
    public function getAppleCertificate()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleCertificate);
    }

    /**
     * Get Apple Certificate Key from admin module setting
     *
     * @return mixed
     */
    public function getAppleCertificateKey()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleCertificateKey);
    }

    /**
     * Get Apple Title from admin module setting
     *
     * @return mixed
     */
    public function getAppleTitle()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleTitle);
    }

    /**
     * Get Apple Button Location from admin module setting
     *
     * @return mixed
     */
    public function getAppleButtonLocation()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleButtonLocation);
    }

    /**
     * Get Apple Button Type from admin module setting
     *
     * @return mixed
     */
    public function getAppleButtonType()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleButtonType);
    }

    /**
     * Get Apple Button Type from admin module setting
     *
     * @return mixed
     */
    public function getAppleButtonTheme()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleButtonTheme);
    }

    /**
     * Get Apple Button Language from admin module setting
     *
     * @return mixed
     */
    public function getAppleButtonLanguage()
    {
        return Mage::helper(self::RootName)->getConfigData(self::APPLEPAY, self::AppleButtonLanguage);
    }

    /**
     * Get Google Merchant Identifier from admin module setting
     *
     * @return mixed
     */
    public function getGoogleMerchantIdentifier()
    {
        return Mage::helper(self::RootName)->getConfigData(self::GOOGLEPAY, self::GoogleMerchantIdentifier);
    }

    /**
     * Get Google Button Type from admin module setting
     *
     * @return mixed
     */
    public function getGoogleButtonType()
    {
        return Mage::helper(self::RootName)->getConfigData(self::GOOGLEPAY, self::GoogleButtonType);
    }

    /**
     * Get APMS selected from admin module setting
     *
     * @return mixed
     */
    public function getAlternativePaymentMethods()
    {
        return Mage::helper(self::RootName)->getConfigData(self::ALTERNATIVE, self::ALTERNATIVE_PAYMENT_METHOD);
    }
}
