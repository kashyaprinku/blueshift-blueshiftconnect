<?php
namespace Blueshift\Blueshiftconnect\Api;
interface SubscriptionInterface {
   /**
    * Get customer name by Customer ID,email,is_subscribed,subscribe_at
    *
    * @api
    * @param int $customerId
    * @param string $email
    * @param string $is_subscribed
    * @param string $subscribe_at
    * @return \Blueshift\Blueshiftconnect\Api\SubcriptionInterface
    * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
    * @throws \Magento\Framework\Exception\LocalizedException
    */
   public function UpdateNewslatterStatus($customerId, $email, $is_subscribed, $subscribe_at);
}