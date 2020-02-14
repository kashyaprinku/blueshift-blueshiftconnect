<?php 
/**
 * Blueshift Blueshiftconnect order creation Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Order;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class OrderCreation implements ObserverInterface {
    protected $logger;
    protected $orderRepository;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param BlueshiftConfig $blueshiftConfig
     * @param OrderRepository $orderRepository
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,\Magento\Sales\Model\OrderRepository $orderRepository,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after order creation event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try{
            $customer_data =  array();
            $eventkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $data = array();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $order = $observer->getOrder();
            $order_details = $order->getData();
            $orders = $order->getAllItems();
            $i=0;
            $orderRepository = $this->orderRepository->get($order_details['id']);
            $items = $observer->getQuote()->getAllItems();
            foreach ($orders as $orderItem) {
                $orderItems = $orderItem->getData();
                $data['events'][$i]['order_id']=$order_details['id'];
                $data['events'][$i]['customer_id']=$order_details['customer_id'];
                $data['events'][$i]['ip']=$order_details['remote_ip'];
                $data['events'][$i]['event']= "purchase"; 
                $data['events'][$i]['storeUrl']=$baseUrl;
                $data['events'][$i]['email']=$order_details['customer_email'];
                $data['events'][$i]['products']['sku']=$orderItems['sku'];
                $data['events'][$i]['products']['qty']=$orderItems['qty_ordered'];
                $data['events'][$i]['products']['price']=$orderItems['price'];
                $data['events'][$i]['products']['product_id']=$orderItems['product_id'];
                $data['events'][$i]['products']['discounted_price']=$orderItems['original_price'];
                $data['events'][$i]['revenue']=$orderRepository->getGrandTotal();
                $i++;
            } 
            $json_data = json_encode($data);
            $path = "bulkevents";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventkey);
            if($result['status']== 200){
                $logger->info("Order creation: status = ok"); 
            }else{
                $result = json_encode($result);
                $logger->info("Order creation: ".$result); 
            } 
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
