<?php 
/**
 * Blueshift Blueshiftconnect Customer update
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Customer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class CustomerUpdate implements ObserverInterface {
    protected $logger;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param QuoteFactory  $quoteFactory,
     * @param Logger $logger
     */
    public function __construct( \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get customer data after customer update from frontend event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer= new \Zend\Log\Writer\Stream(BP .'/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer); 
        try{
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $customerEmail = $observer->getEvent()->getEmail();
            $website_id = $storeManager->getWebsite()->getWebsiteId();
            $customer_factory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
            $customer_data = $customer_factory->create();
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $customer_data->setWebsiteId($website_id);
            $customer_data->loadByEmail($customerEmail);
            $customerData =$customer_data->getData();
            $Addresses =$customer_data->getAddresses();
            foreach ($Addresses as $key => $Address) {
                $customerAddress[] = $Address->toArray();
            }
            $firstName  = $customerData['firstname'] ? $customerData['firstname'] :'';
            $lastName   = $customerData['lastname'] ? $customerData['lastname'] :'';
            $customerID = $customerData['entity_id'];
            $email      = $customerData['email'];
            $genderStatus = $customerData['gender'];
            $dob = $customerData['dob'];
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
            $joined_at  = $customerData['created_at'];
            $telephone  = $customerData['taxvat'];
            $subscribeStatus = $this->blueshiftConfig->subscribeStatus($email);
            if(!empty($subscribeStatus[0]['subscriber_id']) && ($subscribeStatus[0]['subscriber_id'] > 0) ){
                $unsubscribed = true;
                $subscribed_at = $subscribeStatus[0]['change_status_at'];
                $unsubscribed_at = null;
            }else{
                $unsubscribed = false;
                $subscribed_at = null;                
                if(!empty($subscribeStatus[0]['change_status_at']) ){
                    $unsubscribed_at = $subscribeStatus[0]['change_status_at'];
                }else{
                    $unsubscribed_at = null;
                }
            }
            $customer_data =  array();
            $customer_data['firstname'] = $firstName;
            $customer_data['lastname'] = $lastName;
            $customer_data['customer_id'] = $customerID;
            $customer_data['email'] = $email;
            $customer_data['storeUrl']=$baseUrl;
            $customer_data['birth_dayofmonth']= $birth_Day;
            $customer_data['birth_month']= $birth_Month;
            $customer_data['birth_year']= $birth_year;
            $customer_data['joined_at']= $joined_at;
            $i=0; $customer_address = array();
            foreach ($customerAddress as $customerAddres) {
                $customer_data['shipingaddress'][$i]['firstname'] = $customerAddres['firstname'];
                $customer_data['shipingaddress'][$i]['lastname'] = $customerAddres['lastname'];
                $customer_data['shipingaddress'][$i]['telephone'] = $customerAddres['telephone'];
                $customer_data['shipingaddress'][$i]['street']= $customerAddres['street'];
                $customer_data['shipingaddress'][$i]['region'] = $customerAddres['region'];
                $customer_data['shipingaddress'][$i]['postcode'] = $customerAddres['postcode'];
                $customer_data['shipingaddress'][$i]['region_id'] = $customerAddres['region_id'];
                $customer_data['shipingaddress'][$i]['city'] = $customerAddres['city'];
                $customer_data['shipingaddress'][$i]['company'] = $customerAddres['company'];
                $customer_data['shipingaddress'][$i]['region_id'] = $customerAddres['region_id'];
                $i++;
            } 
            $customer_data['phone_number']= $telephone;
            $customer_data['gender']= $gender;
            $customer_data['unsubscribed']= $unsubscribed;
            $customer_data['subscribed_at']= $subscribed_at;
            $customer_data['unsubscribed_at']=$unsubscribed_at;
            $json_data = json_encode($customer_data);       
            $path = "customers";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $logger->info("Customer Update: status = ok"); 
            }else{
                $result = json_encode($result);
                $logger->info("Customer Update: ".$result);
            }  
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
