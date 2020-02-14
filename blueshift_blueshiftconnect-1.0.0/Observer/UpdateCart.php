<?php 
/**
 * Blueshift Blueshiftconnect Addcart Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class UpdateCart implements \Magento\Framework\Event\ObserverInterface {
    protected $logger;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param QuoteFactory  $quoteFactory,
     * @param Logger $logger
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after on product updated complete state event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer); 
        try{
            $data =  array();
            $eventkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $objectManager =\Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $quote = $cart->getQuote();
            $items = $quote->getAllItems();
            $cart_details = $observer->getEvent()->getQuoteItem();
            $cart_total = $quote->getData();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $i=0;
            $data['customer_id'] = $customerSession->getCustomerId();
            $data['email'] = $customerSession->getCustomer()->getEmail();
            $data['event'] = "add_to_cart";
            $data['storeUrl'] = $baseUrl;
            foreach ($items as $item) { 
                $product = $item->getData(); 
                $data['products'][$i]['price']=$item->getPrice();
                $data['products'][$i]['name']=$product['name'];
                $data['products'][$i]['sku']=$product['sku'];
                $data['products'][$i]['qty']=$product['qty'];
                $i++;  
            }
            $json_data=json_encode($data); 
            $path = "event";
            $method = "POST";
            $result=$this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventkey);
            if($result['status']== 200){
                $logger->info("Update cart: status = ok"); 
            }else{
                $result = json_encode($result);
                $logger->info("Update cart: ".$result); 
            } 
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
