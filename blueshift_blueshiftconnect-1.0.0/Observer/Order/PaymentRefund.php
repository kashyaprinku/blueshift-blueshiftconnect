<?php 
/**
 * Blueshift Blueshiftconnect
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Order;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class PaymentRefund implements ObserverInterface {
    protected $quoteFactory;
    protected $logger;
    protected $orderRepository;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param OrderRepository $orderRepository
     * @param BlueshiftConfig $blueshiftConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,\Magento\Sales\Model\OrderRepository $orderRepository,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after Payment Refund event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $customer_data =  array();
            $eventapikey=$this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $data = array();
            $i=0;
            $creditMemo = $observer->getEvent()->getCreditmemo();
            $order      = $creditMemo->getOrder();
            $order_details = $order->getData();
            $orders = $order->getAllItems();
            foreach ($orders as $orderItem) {
                $orderItems = $orderItem->getData();
                $customer = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($orderItems['order_id']);
                $orderRepository = $this->orderRepository->get($orderItems['order_id']);
                $data['order_id']= $orderItems['order_id'];
                $data['customer_id']= $customer->getCustomerId();
                $data['event']= "payment_refund";
                $data['storeUrl']=$baseUrl;
                $data['email']= $customer->getCustomerEmail();
                $data['products'][$i]['parent_sku']=$orderItems['sku'];
                $data['products'][$i]['name']=$orderItems['name'];
                $data['products'][$i]['qty']=$orderItems['qty_refunded'];
                $data['products'][$i]['product_id']=$orderItems['product_id'];
                $data['amount_refunded']=$orderRepository->getGrandTotal();
                $i++;  
            }
            $json_data = json_encode($data);
            $path = "event";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventapikey);
            if($result['status']== 200){  
                $logger->info("payment_refund: status = ok");
            }else{
                $result = json_encode($result);
                $logger->info("payment_refund: ".$result); 
            }
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }

    }
}
