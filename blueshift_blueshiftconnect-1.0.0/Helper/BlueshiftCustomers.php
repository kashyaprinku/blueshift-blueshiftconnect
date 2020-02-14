<?php
/**
* Blueshift Blueshiftconnect Blueshift Helper
* @category  Blueshift
* @package   Blueshift_Blueshiftconnect
* @author    Blueshift
* @copyright Copyright (c) Blueshift(https://blueshift.com/)
*/
namespace Blueshift\Blueshiftconnect\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
use Magento\Framework\Serialize\SerializerInterface;
class BlueshiftCustomers extends AbstractHelper
{
    private $serializer;
    public function __construct(SerializerInterface $serializer,\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, BlueshiftConfig $blueshiftConfig) {
        $this->blueshiftConfig = $blueshiftConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function getCustomersTotalCount(){
        try {
            $data = array();
            $data['totalCount'] = $this->_scopeConfig->getValue('blueshiftconnect/step2/customercount');
            $data['CurPageSize'] = 10;
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'Blueshift');
        }  
        return $data;
    }
    public function sendCustomersData($step,$CurPageSize){
        try {
            $start_date=$this->_scopeConfig->getValue('blueshiftconnect/step2/start_date');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $total_customer = $this->_scopeConfig->getValue('blueshiftconnect/step2/customercount');
            if ($total_customer == 0) {
                $LogMsg ="Customers: status = completed";
                return false;
            }
            $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->getCollection()->setPageSize($CurPageSize)->setCurPage($step);
            $i = 0;
            foreach($customerObj as $customerObjdata ) {
                $customer = $customerObjdata ->getData();
                $customerID  = $customer['entity_id'];
                $firstName = $customer['firstname'] ? $customer['firstname'] : '';
                $lastName =  $customer['lastname'] ? $customer['lastname'] : '';
                $email  = $customer['email'];
                $joined_at = $customer['created_at'];
                $genderStatus = $customer['gender'];
                $dob = $customer['dob'];
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
                $city ='';$country_id='';$state='';$postcode='';$telephone='';$country='';
                $billingAddress = $customerObjdata->getAddresses();
                foreach ($billingAddress as $customerAddres) {
                    $city = $customerAddres['city'] ? $customerAddres['city'] : null;
                    $country_id = $customerAddres['country_id'];
                    $state = $customerAddres['region'];
                    $postcode = $customerAddres['postcode'];
                    $telephone = $customerAddres['telephone'];
                    $country = $objectManager->create('\Magento\Directory\Model\Country')->load($country_id)->getName();
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
                $customerData['customers'][$i]['firstname']=$firstName ? $firstName : '';
                $customerData['customers'][$i]['lastname'] = $lastName ? $lastName : '';
                $customerData['customers'][$i]['customer_id'] = $customerID;
                $customerData['customers'][$i]['email'] = $email;
                $customerData['customers'][$i]['storeUrl']=$baseUrl;
                $customerData['customers'][$i]['birth_dayofmonth']= $birth_Day;
                $customerData['customers'][$i]['birth_month']= $birth_Month;
                $customerData['customers'][$i]['birth_year']= $birth_year;
                $customerData['customers'][$i]['joined_at']= $joined_at;
                $customerData['customers'][$i]['last_location_city']= $city;
                $customerData['customers'][$i]['last_location_country']= $country;
                $customerData['customers'][$i]['last_location_country_code']= $country_id;
                $customerData['customers'][$i]['last_location_pin_code']= $postcode;
                $customerData['customers'][$i]['last_location_state']= $state;
                $customerData['customers'][$i]['phone_number']= $telephone;
                $customerData['customers'][$i]['gender']= $gender;
                $customerData['customers'][$i]['unsubscribed']= $unsubscribed;
                $customerData['customers'][$i]['subscribed_at']= $subscribed_at;
                $customerData['customers'][$i]['unsubscribed_at']= $unsubscribed_at;
                $i++;
            }
            $json_data = json_encode($customerData);
            $path = "customers/bulk";
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                if(count($result['response'])>0){
                    $sync_type = "customers";
                    $customers = $result['response']['customers'];
                    $count = count($customers);
                    $errors = $result['response']['errors'];
                    for($i= 0; $i < $count; $i++) {
                        $error = addslashes($this->serializer($errors[$i]));
                        $errors_value = $sync_type.' '.$customers[$i]['customer_id'].' '.$error;
                        $this->blueshiftConfig->loggerWrite($errors_value,'Blueshift');
                    }
                }
            }else{
                $result = json_encode($result);
                $LogMsg = "customers: ".$result;
                $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
            }
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'Blueshift');
        }  
    }     
}
