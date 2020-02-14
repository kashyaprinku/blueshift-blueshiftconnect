<?php 
/**
 * Blueshift Blueshiftconnect 
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Checkout;
use Magento\Framework\Event\ObserverInterface;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
use Magento\Checkout\Model\Cart as CustomerCart;

class CheckoutCreate implements ObserverInterface {
    protected $cart;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param ScopeConfigInterface $scopeConfig,
     * @param BlueshiftConfig $blueshiftConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig, CustomerCart $cart)
     {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
        $this->cart = $cart;
    }

    /**
     * get checkout data after on product added event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    { 
        try {
            $eventkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $data = array();
            $quote = $this->cart->getQuote();
            $Items = $quote->getAllItems();
            $quote_data=  $quote->getData();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $data['customer_id'] = $quote_data['customer_id'];
            $data['event'] = "checkout";
            $data['storeUrl']=$baseUrl;
            $data['total'] = $quote_data['grand_total'];
            $data['email'] = $quote_data['customer_email'];
            $data['qty'] = $quote_data['items_qty'];
            $data['ip'] = $quote_data['remote_ip'];
            $i=0;
            foreach ($Items as $item) {
              	$item_data = $item->getData();
              	if($item_data['product_type']== "simple"){
              		$data['products'][$i]['parent_sku']= $item_data['sku'];
              		$data['products'][$i]['title']= $item_data['name'];
              		$data['products'][$i]['product_id']= $item_data['product_id'];
              	}
             	$i++;
            }
            $json_data = json_encode($data);
            $path = "event";
            $method = "POST";
            $result =$this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventkey);
            if($result['status']== 200){
              $this->blueshiftConfig->loggerWrite("Checkout creation: status = ok",'observer');
            }else{
                $result = json_encode($result);
                $this->blueshiftConfig->loggerWrite("Checkout creation: ".$result,'observer');
            } 
        }catch (\Exception $e) {
          $this->blueshiftConfig->loggerWrite($e->getMessage(),'observer');
        }
    }
}
