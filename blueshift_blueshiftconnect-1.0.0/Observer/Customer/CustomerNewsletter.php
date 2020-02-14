<?php 
/**
 * Blueshift Blueshiftconnect Customer subcribers
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Customer;
use Magento\Framework\Event\ObserverInterface;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class CustomerNewsletter implements ObserverInterface 
{
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param \Magento\Framework\UrlInterface $url,
     * @param BlueshiftConfig $blueshiftConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, BlueshiftConfig $blueshiftConfig) 
    {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get customer data on customer subscrber event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    { 
        try {
            $customer_data =  array();
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $subscriberData = $observer->getEvent()->getSubscriber();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $customerID = $subscriberData->getCustomerId();
            $email = $subscriberData->getSubscriberEmail();
            $subscriberStatus = $subscriberData->getSubscriberStatus();
            $ChangeStatusAt = $subscriberData->getChangeStatusAt();
            $subscriberId = $subscriberData->getSubscriberId();
            if ($subscriberStatus == 1) {
                $unsubscribed = true;
                $subscribed_at = $ChangeStatusAt;
                $unsubscribed_at = null;
            } else{
                $unsubscribed = false;
                $unsubscribed_at = $ChangeStatusAt;
                $subscribed_at = null;
            }
            $customer_data['customers'][0]['customer_id'] = $customerID;
            $customer_data['customers'][0]['email'] = $email;
            $customer_data['customers'][0]['storeUrl']=$baseUrl;
            $customer_data['customers'][0]['unsubscribed']= $unsubscribed;
            $customer_data['customers'][0]['subscribed_at']= $ChangeStatusAt;
            $customer_data['customers'][0]['unsubscribed_at']= $unsubscribed_at;
            $json_data = json_encode($customer_data);
            $path = "customers/bulk";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data, $path, $method, $password, $userkey);
            if($result['status']== 200){
                $this->blueshiftConfig->loggerWrite("Customer Subcriber: status = ok",'observer');
            }else{
                $result = json_encode($result);
                $this->blueshiftConfig->loggerWrite("Customer Subcriber: ".$result,'observer');
            } 
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'observer');
        }
    }
}
