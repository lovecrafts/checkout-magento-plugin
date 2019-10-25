<?php

/**
 * Use to loaded Checkout.com autoloader
 *
 * Class Checkoutcom_Ckopayment_Model_Autoloader
 */
class Checkoutcom_Ckopayment_Model_Autoloader extends Varien_Event_Observer
{
    /**
     * So bad, we have a normal autoloader for composer based libs
     * @param $event
     */
    public function controllerFrontInitBefore( $event ) {
        //load Zend_Loader
        require_once  Mage::getBaseDir().('/lib/Zend/Loader.php');

        //instantiate a zend autoloader first, since we
        //won't be able to do it in an unautoloader universe
        $autoLoader = Zend_Loader_Autoloader::getInstance();

        //get a list of call the registered autoloader callbacks
        //and pull out the Varien_Autoload.
        $autoloader_callbacks = spl_autoload_functions();
        $original_autoload=null;
        foreach($autoloader_callbacks as $callback)
        {
            if(is_array($callback) && $callback[0] instanceof Varien_Autoload)
            {
                $original_autoload = $callback;
            }
        }

        //remove the Varien_Autoloader from the stack
        spl_autoload_unregister($original_autoload);

        //register CKO autoloader, which gets on the stack first
        require_once Mage::getBaseDir('lib') . "/checkout-sdk-php/checkout.php";
        $autoLoader->pushAutoloader(array('checkout', 'load'), true);

        //IMPORTANT: add the Varien_Autoloader back to the stack
        spl_autoload_register($original_autoload);
    }
}
