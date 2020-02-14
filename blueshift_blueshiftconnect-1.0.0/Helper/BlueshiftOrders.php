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
class BlueshiftOrders extends AbstractHelper
{
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig , BlueshiftConfig $blueshiftConfig) {
        $this->blueshiftConfig = $blueshiftConfig;
        $this->_scopeConfig = $scopeConfig;
    }

    public function getOrdersTotalCount()
    {
        try {
            $data = array();
            $data['totalCount'] = $this->_scopeConfig->getValue('blueshiftconnect/step2/ordercount');
            $data['CurPageSize'] = 20;
        } catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
        return $data;
    }
    public function sendOrdersData($step, $CurPageSize){
        try {
            $start_date=$this->_scopeConfig->getValue('blueshiftconnect/step2/start_date');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $results = $this->blueshiftConfig->getSyncStatus("orders");
            $total_orders = $this->_scopeConfig->getValue('blueshiftconnect/step2/ordercount');
            if ($total_orders == 0)
            {
                $LogMsg ="Orders: status = completed";
                return false;
            }
            $orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection()->addFieldToFilter('created_at', array('gteq' => $start_date))->setPageSize($CurPageSize)->setCurPage($step);
            $itemQty = array();
            $i=0;
            foreach($orderDatamodel as  $Customer_order)
            {
                $orderItems = $Customer_order->getData();
                $itemQty['events'][$i]['order_id']=$orderItems['entity_id'];
                $itemQty['events'][$i]['customer_id']=$orderItems['customer_id'];
                $itemQty['events'][$i]['event']= "purchase";
                $itemQty['events'][$i]['storeUrl']=$baseUrl;
                $itemQty['events'][$i]['revenue']=$orderItems['grand_total'];
                $itemQty['events'][$i]['email']=$orderItems['customer_email'];
                $itemQty['events'][$i]['ip']=$orderItems['remote_ip'];
                $order_obj = $objectManager->create('Magento\Sales\Model\Order')->load(intval($orderItems['entity_id']));
                $orderItems = $order_obj->getAllItems();
                if(!empty($orderItems)){
                    foreach ($orderItems as $item)
                    {
                        $data = $item->getData(); 
                        $itemQty['events'][$i]['products']['sku'] = $data['sku'];
                        $itemQty['events'][$i]['products']['qty'] = $data['qty_ordered'];
                        $itemQty['events'][$i]['products']['price'] = $data['price'];
                        $itemQty['events'][$i]['products']['discounted_price'] = $data['original_price'];
                    }
                }
                $i++;
            }
            $json_data = json_encode($itemQty);
            $path = "bulkevents";
            $eventkey=$this->_scopeConfig->getValue('blueshiftconnect/Step1/eventapikey');
            $password = '';
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data, $path, $method, $password, $eventkey);
            if($result['status'] != 200)
            {
                $result = json_encode($result);
                $LogMsg = "customers: ".$result;
                $this->blueshiftConfig->loggerWrite($LogMsg, 'Blueshift');
            }
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
    }     
}
