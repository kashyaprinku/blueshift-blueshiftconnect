<?php 
/**
 * Blueshift Blueshiftconnect Customer deletion
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Customer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
class CustomerDelete implements ObserverInterface {
    protected $logger;
    /**
     * @param \Magento\Framework\UrlInterface $url,
     * @param ScopeConfigInterface $scopeConfig,
     * @param BlueshiftConfig $blueshiftConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get customer data after customer delete event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        try {
        	$userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $customer_data =  array();
            $customer = $observer->getEvent()->getCustomer();
            $customerID = $customer->getId();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $email = $customer->getEmail();
            $customer_data['email']= $email;
            $customer_data['storeUrl']=$baseUrl;
            $json_data = json_encode($customer_data);
            $path = "customers/delete?delete_all_matching_customers=true";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $this->blueshiftConfig->loggerWrite("Customer Delete: status = ok",'observer');
            }else{
                $result = json_encode($result);
                $this->blueshiftConfig->loggerWrite("Customer Delete: ".$result,'observer');
            } 
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'observer');
        }
    }
}    