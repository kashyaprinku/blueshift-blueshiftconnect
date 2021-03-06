<?php 
/**
 * Blueshift Blueshiftconnect Customer creation
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Customer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class CustomerRegister implements ObserverInterface {
    protected $logger;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param \Magento\Framework\UrlInterface $url
     * @param BlueshiftConfig $blueshiftConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get customer data after customer added from frontend event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try{
            $customer_data =  array();
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $customer = $observer->getEvent()->getCustomer();
            $email = $customer->getEmail();
            $customerID = $customer->getId();
            $firstName = $customer->getFirstname();
            $lastName = $customer->getLastname();
            $joined_at = $customer->getCreatedAt();
            $genderStatus = $customer->getGender();
            $dob = $customer->getDob();
            $time  = strtotime($dob);
            $birth_Day   = date('d',$time) ? date('d',$time) : '';
            $birth_Month = date('m',$time) ? date('m',$time) : '';
            $birth_year  = date('Y',$time) ? date('Y',$time) : '';
            if ($genderStatus == 1) {
                $gender = 'male';
            } elseif ($genderStatus == 2) {
                $gender = 'female';
            } else {
                $gender ='';
            }
            $sub_result = $this->blueshiftConfig->subscribeStatus($email);
            if(!empty($sub_result[0]['subscriber_id']) && ($sub_result[0]['subscriber_id'] > 0) ){
                $unsubscribed = true;
                $subscribed_at = $sub_result[0]['change_status_at'];
                $unsubscribed_at = null;

            }else{
                $unsubscribed = false;
                $subscribed_at = null;                
                if(!empty($sub_result[0]['change_status_at']) ){
                    $unsubscribed_at = $sub_result[0]['change_status_at'];
                }else{
                    $unsubscribed_at = null;
                }
            }

            $customer_data['customers'][0]['firstname'] = $firstName;
            $customer_data['customers'][0]['lastname'] = $lastName;
            $customer_data['customers'][0]['customer_id'] = $customerID;
            $customer_data['customers'][0]['email'] = $email;
            $customer_data['customers'][0]['storeUrl']=$baseUrl;
            $customer_data['customers'][0]['gender']= $gender;
            $customer_data['customers'][0]['birth_dayofmonth']= $birth_Day;
            $customer_data['customers'][0]['birth_month']= $birth_Month;
            $customer_data['customers'][0]['birth_year']= $birth_year;
            $customer_data['customers'][0]['joined_at']= $joined_at;
            $customer_data['customers'][0]['unsubscribed']= $unsubscribed;
            $customer_data['customers'][0]['subscribed_at']= $subscribed_at;
            $customer_data['customers'][0]['unsubscribed_at']=$unsubscribed_at;
            
            $json_data = json_encode($customer_data);
            $logger->info($json_data);
            $path = "customers/bulk";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $logger->info("Customer creation: status = ok"); 
            }else{
                $result = json_encode($result);
                $logger->info("Customer creation: ".$result);
            }  
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
