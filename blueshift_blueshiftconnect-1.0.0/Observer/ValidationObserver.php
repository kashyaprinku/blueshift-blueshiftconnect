<?php 
/**
 * Blueshift Blueshiftconnect 
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class ValidationObserver implements ObserverInterface
{
    protected $logger;

     /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param WriterInterface $configWriter,
     * @param Logger $logger
     * @param OrderRepository $orderRepository
     */

    public function __construct(\Magento\Framework\App\Config\Storage\WriterInterface $configWriter,BlueshiftConfig $blueshiftConfig) {
        $this->_configWriter = $configWriter;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get blueshift configration details after save config event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(EventObserver $observer){   
        $writer = new \Zend\Log\Writer\Stream(BP .'/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $Firstname = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getFirstname();
            $Lastname = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getLastname();
            $User_email = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getEmail();
            $User_id = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getId();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $user_details = array(
                "firstname"=>$Firstname,
                "lastname"=>$Lastname,
                "customer_id"=>$User_id,
                "email"=>$User_email,
                "storeUrl"=> $baseUrl
            );

            $event_details = array(
                "event"=>'identify',
                "email"=>$User_email,
                "storeUrl"=> $baseUrl
            );
            $json_data = json_encode($user_details);
            $event_json = json_encode($event_details);
            $group  = filter_input(INPUT_POST, 'groups', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
           
             $userapikey=$group['Step1']['fields']['userapikey'];
             $eventapikey=$group['Step1']['fields']['eventapikey'];
             $validate_value=$group['Step1']['fields']['validate_value'];
            $password = '';
            $eventkey = $eventapikey['value'];
            $userkey = $userapikey['value'];
            $path = "customers";
            $event_path = "event";
            $method = 'POST';
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $result = $this->blueshiftConfig->curlFunc($event_json,$event_path,$method,$password,$eventkey);
                if($result['status'] == 200){
                    $this->_configWriter->save('blueshiftconnect/Step1/validate_value',1,'default', 0);
                    $logMsg = '{"status":"ok"}';
                }else{
                    $this->_configWriter->save('blueshiftconnect/Step1/validate_value',0,'default', 0);
                    $this->_configWriter->save('blueshiftconnect/Step1/userapikey','','default', 0);
                    $this->_configWriter->save('blueshiftconnect/Step1/eventapikey','','default', 0);
                    $result = json_encode($result);
                    $logMsg ="Vallidation response: ".$result;
                } 
            }else{
                $this->_configWriter->save('blueshiftconnect/Step1/validate_value',0,'default', 0);
                $this->_configWriter->save('blueshiftconnect/Step1/userapikey','','default', 0);
                $this->_configWriter->save('blueshiftconnect/Step1/eventapikey','','default', 0);
                $result = json_encode($result);
                $logMsg ="Vallidation response: ".$result; 
            }
            $logger->info($logMsg);
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
