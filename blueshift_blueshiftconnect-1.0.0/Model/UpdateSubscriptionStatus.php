<?php
namespace Blueshift\Blueshiftconnect\Model;
use Blueshift\Blueshiftconnect\Api\SubscriptionInterface;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class UpdateSubscriptionStatus implements SubscriptionInterface{
   /**
    * @var CustomerRegistry
    */
   public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, BlueshiftConfig $blueshiftConfig){  
       $this->_scopeConfig = $scopeConfig;
       $this->blueshiftConfig = $blueshiftConfig;
   }
   /**
    * Get customer's name by Customer ID and return greeting message.
    * @api
    * @param int $customerId
    * @param string $email
    * @param string $is_subscribed
    * @param string $subscribe_at
    * @return \Magento\Customer\Api\Data\CustomerInterface
    * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
    * @throws \Magento\Framework\Exception\LocalizedException
    */

   public function UpdateNewslatterStatus($customerId, $email, $is_subscribed, $subscribe_at){
      try{
        $SubscriptionUpdateallow = $this->_scopeConfig->getValue('blueshiftconnect/step2/allow_customer_group');
        if($SubscriptionUpdateallow){
          $dateTime = strtotime($subscribe_at);
          $subscribe_at  = date("Y-m-d H:i:s", $dateTime);
          $result = $this->blueshiftConfig->UpdateSubscribeStatus($customerId, $email, $is_subscribed, $subscribe_at);
        }else{
          $result = "{'status': 'fail'}";
        }
        $this->blueshiftConfig->loggerWrite($result, 'apilog');
        return $result;
      }catch (\Exception $e) {
        $this->blueshiftConfig->loggerWrite($e->getMessage(), 'apilog');
      }
   }
}