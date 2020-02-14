<?php
/**
 * Blueshift Blueshiftconnect Addcart Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Controller\Adminhtml\Actionajax;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
class ValidationAction extends \Magento\Backend\App\Action
{   
    public function __construct(Context $context, BlueshiftConfig $blueshiftConfig)
    {
        parent::__construct($context);
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * API keys validation from backend.
     *
     * @param execute
     */
    public function execute()
    {
        try {
            $response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $userkey = filter_input(INPUT_POST, "userkey");
            $eventkey = filter_input(INPUT_POST, "eventkey"); 
            $password = "";
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $Firstname = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getFirstname();
            $Lastname = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getLastname();
            $User_email = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getEmail();
            $User_id = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser()->getId();
            /**
             * API keys data array for User details
             *
             * @param $user_details
             */
            $user_details = array("firstname"=>$Firstname,"lastname"=>$Lastname,
                "customer_id"=>$User_id,"email"=>$User_email);
            /**
             * API keys data array for Event details
             *
             * @param $event_details
             */
            $event_details = array("event"=>'identify', "email"=>$User_email);
            $event_json = json_encode($event_details);
            $json_data = json_encode($user_details);
            $path = "customers";
            $event_path = "event";
            $method = 'POST';
            $result = $this->blueshiftConfig->curlFunc($json_data, $path, $method, $password, $userkey);
            if($result['status']== 200)
            {
                $result = $this->blueshiftConfig->curlFunc($event_json, $event_path, $method, $password, $eventkey);
                if($result['status'] == 200)
                {
                    $logMsg = '{"status":"ok"}';
                    $response->setContents( $logMsg );
                }else{
                    $response->setContents( '{"status":"unauthorized"}' );
                    $result = json_encode($result);
                    $logMsg = '{"response":"'.$result.'"}';
                } 
            }else{
                $result = json_encode($result);
                $response->setContents('{"status":"not_valid"}');
                $logMsg = '{"status":"not_valid", "response":"'.$result.'"}';
            }
            $this->blueshiftConfig->loggerWrite($logMsg, 'Blueshift');
            return $response; 
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }
    }     
}
