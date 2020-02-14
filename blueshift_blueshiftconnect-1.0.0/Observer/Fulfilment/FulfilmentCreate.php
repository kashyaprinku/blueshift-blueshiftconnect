<?php 
/**
 * Blueshift Blueshiftconnect Fulfilment creation Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Fulfilment;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class FulfilmentCreate implements ObserverInterface {
    protected $logger;
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param Repository $assetRepos,
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after on fulfilment order event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/observer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $eventkey=$this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $data = array();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();
            $orderItems = $order->getAllItems();
            $order_details = $order->getData();
            $data['storeUrl']=$baseUrl;
            $data['email'] = $order_details['customer_email'];
            $data['customer_id'] = $order_details['customer_id'];
            $data['event'] = "fulfilment_creation";
            $data['shipping_method'] = $order_details['shipping_method'];
            $data['shipping_amount'] = $order_details['base_shipping_amount'];
            $data['revenue'] = $order_details['grand_total'];
            $data['ip'] = $order_details['remote_ip'];
            $data['currency_code'] = $order_details['order_currency_code'];
            $address = $objectManager->get('\Magento\Sales\Model\Order');
            $addressData = $address->load($order_details['entity_id']);
            $shipAdd = $addressData->getShippingAddress()->getData();
            $data['city'] = $shipAdd['city'];
            $country_id = $shipAdd['country_id'];
            $data['region'] = $shipAdd['region'];
            $data['postcode'] = $shipAdd['postcode'];
            $data['telephone'] = $shipAdd['telephone'];
            $data['country'] = $objectManager->create('\Magento\Directory\Model\Country')->load($country_id)->getName();
            if(!empty($orderItems)){
                $i = 0;
                foreach ($orderItems as $orderItem) {
                    $productData = $orderItem->getData();
                    $data['products'][$i]['parent_sku'] = $productData['sku'];
                    $data['products'][$i]['product_id'] = $productData['product_id'];
                    $i++;
                }
            }
            $json_data = json_encode($data);
            $logger->info($json_data);
            $path = "event";
            $method = "POST";
            $result=$this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$eventkey);
            if($result['status']== 200){
                $logMsg = "Fulfilment: status = ok";
            }else{
                $result = json_encode($result);
                $logMsg = "Fulfilment: ".$result;  
            }
            $logger->info($logMsg); 
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
}
