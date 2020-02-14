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
class BlueshiftAbandonedCarts extends AbstractHelper
{
    public function __construct( \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig , \Magento\Catalog\Model\ProductFactory $productFactory , BlueshiftConfig $blueshiftConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    public function getAbandonedcartsTotalCount()
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
    public function sendAbandonedcartsData($step, $CurPageSize)
    {
        try {
            $totalCarts = $this->_scopeConfig->getValue('blueshiftconnect/step2/ordercount');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $results = $this->blueshiftConfig->getActiveQuote();
            if ($totalCarts == 0) 
            {
                $LogMsg ="Abandoned Carts: status = completed";
                $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
                return false;
            }
            $data = array();
            $i=0;
            if(!empty($results))
            {
                foreach ($results as $key => $result)
                {
                    if(!empty($result['customer_id']))
                    { 
                    $QuoteItems=$this->blueshiftConfig->getQuoteItems($result['entity_id']);
                    $data['events'][$i]['event']= "checkout";
                    $data['events'][$i]['storeUrl']=$baseUrl;
                    $data['events'][$i]['customer_id']=$result['customer_id'];
                    $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                    $customer = $customerFactory->load($result['customer_id']); 
                        $data['events'][$i]['email']=$customer->getEmail();
                        $count=0;
                        foreach($QuoteItems as $QuoteItem){
                            $data['events'][$i]['products'][$count]['sku'] = $QuoteItem['sku'];
                            $product=$this->productFactory->create();
                            $data['events'][$i]['products'][$count]['price']=$QuoteItem['price'];
                            $data['events'][$i]['products'][$count]['qty'] = $QuoteItem['qty'];
                            $count++;
                        }
                        $data['events'][$i]['revenue'] = $result['grand_total'];
                        $data['events'][$i]['ip'] = $result['remote_ip']; 
                    }
                    $i++;  
                } 
            }
            $json_data = json_encode($data);
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
        } catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
    }     
}
