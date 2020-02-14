<?php 
/**
 * Blueshift Blueshiftconnect order cancellation Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Order;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
class OrderCancel implements ObserverInterface {
    protected $logger;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param QuoteFactory  $quoteFactory,
     * @param Logger $logger
     * @param OrderRepository $orderRepository
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after order cancelled event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try{
            $customer_data =  array();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $eventkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $data = array();
            $order = $observer->getOrder();
            $order_details = $order->getData();
            $data['storeUrl']=$baseUrl;
            $data['order_id'] = $order_details['entity_id'];
            $data['customer_id'] = $order_details['customer_id'];
            $data['email'] = $order_details['customer_email'];
            $data['revenue'] = $order_details['grand_total'];
            $data['ip'] = $order_details['remote_ip'];
            $data['event'] = "order_cancelled";
            $orders = $order->getAllItems();
            $count=0;
            foreach ($orders as $key => $value) {
                $ordersitem = $value->getData();
                $data['products'][$count]['parent_sku']=$ordersitem['sku'];
                $data['products'][$count]['title']=$ordersitem['name'];
                $data['products'][$count]['qty'] = $ordersitem['qty_canceled'];
                $count++;
            }
            $json_data = json_encode($data);
            $path = "event";
            $method = "POST";
            $result=$this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventkey);
            $logger->info($result); 
            if($result['status']== 200){
                $logger->info("Order cancellation: status = ok"); 
            }else{
                $result = json_encode($result);
                $logger->info("Order cancellation: ".$result);
            } 
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
