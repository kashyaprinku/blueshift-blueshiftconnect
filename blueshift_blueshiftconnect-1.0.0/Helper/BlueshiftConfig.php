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
use Psr\Log\LoggerInterface as Logger;
class BlueshiftConfig extends AbstractHelper 
{
    const CURLURL = "https://api.getblueshift.com/api/v1/";
	protected $logger;
    /**
    * @param Logger $logger
    */  
	public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig) {
        $this->_scopeConfig = $scopeConfig;
    }
    /**
    * @param SubscribeStatus for geting customer Subscribed status by customer email
    */  

    public function loggerWrite($logMsg,$logFileName){
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$logFileName.'.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($logMsg);
        return true;
    }

    public function subscribeStatus($email){
        $data = array();
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $table_name=$resource->getTableName('newsletter_subscriber');
        $sub_sql="SELECT * FROM ".$table_name." WHERE subscriber_email='".$email ."' && subscriber_status = 1";
        $results = $connection->fetchAll($sub_sql);
        foreach ($results as $key => $result) {
           $data[$key] = $result;
        }
        return $data;
    }

    /**
    * @param UpdateSubcribeStatus for updatesubcriberstatus
    */ 

    public function UpdateSubscribeStatus($customerId,$email,$is_subscribed,$subscribe_at){
        if($is_subscribed=='true')
        {
            $status = 1;
        }else{
            $status = 3;
        }
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $table_name=$resource->getTableName('newsletter_subscriber');
        $sub_sql="UPDATE ".$table_name." SET subscriber_status = ".$status.",change_status_at = '".$subscribe_at."' WHERE subscriber_email ='".$email ."' && customer_id = ".$customerId." ";
        $result = $connection->query($sub_sql);
        $msg = "{'status': 'ok'}";
        return $msg;
    }

    /**
    * @param getProductMsrp for geting product Msrp price by product id
    */  
    public function getProductMsrp($ProductId){
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableEav_attribute = $resource->getTableName('eav_attribute');
        $sql = "SELECT attribute_id FROM ".$tableEav_attribute ." WHERE attribute_code = 'msrp'";
        $msrpId = $connection->fetchAll($sql);
        $tableMsrp = $resource->getTableName('catalog_product_entity_decimal');
        $sub_sql ="SELECT * FROM ".$tableMsrp ." WHERE entity_id ='".$ProductId."' && attribute_id='".$msrpId[0]['attribute_id']."' ";
        $msrpResult = $connection->fetchAll($sub_sql);
        if(!empty($msrpResult[0]['value_id'])&&($msrpResult[0]['value_id'] > 0))
        {
            $msrp = $msrpResult[0]['value'];
        }else{
            $msrp = '';
        }
        return $msrp;
    }
     /**
    * @param getProductManufacturer for geting product Manufacturer  by product id
    */  
    public function getProductManufacturer($ProductId)
    {
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableEav_attribute = $resource->getTableName('eav_attribute');
        $sql = "SELECT attribute_id FROM ".$tableEav_attribute ." WHERE attribute_code = 'manufacturer'";
        $manufacturerId = $connection->fetchAll($sql);
        $tableName = $resource->getTableName('catalogsearch_fulltext_scope1');
        $query = "SELECT * FROM ".$tableName." WHERE entity_id = '".$ProductId."' && attribute_id = '".$manufacturerId[0]['attribute_id']."'";
        $mfrResult = $connection->fetchAll($query);
        if(!empty($mfrResult[0]['data_index']) ){
            $manufacturer = $mfrResult[0]['data_index'];
        }else{
            $manufacturer = '';
        }
        return $manufacturer;
    }
    /**
    * @param getProductVisible for check Visible product and set Qty by product id
    */  
    public function getProductVisible($ProductId , $Qty=0)
    {
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableEav_attribute = $resource->getTableName('eav_attribute');
        $sql = "SELECT attribute_id FROM ".$tableEav_attribute ." WHERE attribute_code = 'visibility'";
        $visibilityId = $connection->fetchAll($sql);
        $tableName = $resource->getTableName('catalog_product_entity_int');
        $query = "SELECT * FROM " . $tableName ." WHERE entity_id = '". $ProductId ."' && attribute_id = '".$visibilityId[0]['attribute_id']."'";
        $visibleResult = $connection->fetchAll($query);
        if(!empty($visibleResult[0]['value'])&&($visibleResult[0]['value']==1))
        {
             $Qty = 0;
        }
        return $Qty;
    }
    /**
    * @param getActiveQuote for get all active Quotes from database
    */  
    public function getActiveQuote()
    {
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableName = $resource->getTableName('quote');
        $sql = 'SELECT * FROM '.$tableName.' WHERE is_active = "1" AND customer_id IS NOT NULL';
        $results = $connection->fetchAll($sql);
        return $results;
    }
    /**
    * @param getQuoteItems for get all quote items from QuoteId
    */  
    public function getQuoteItems($quoteId)
    {
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableName=$resource->getTableName('quote_item');
        $quote_data = 'SELECT * FROM ' .$tableName.' WHERE quote_id = "'.$quoteId.'" AND parent_item_id IS NULL';
        $results = $connection->fetchAll($quote_data);
        return $results;
    }
    /**
    * @param getSyncStatus for get sync cron status from database
    */  
    public function getSyncStatus($syncType)
    {
        $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
        $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection=$resource->getConnection();
        $tableName=$resource->getTableName('blueshift_cron_activities');
        $sql = 'SELECT * FROM ' .$tableName.' WHERE sync_type = "'.$syncType.'"';
        $results = $connection->fetchAll($sql);
        return $results;
    }

    public function getSyncActivityStatus()
    {
        $status = $this->_scopeConfig->getValue('blueshiftconnect/step2/synchronizationststus');
        return $status;
    }
    /**
    * @param CronActivitiesUpdation for updating cron status in database
    */ 
    public function CronActivitiesUpdation($pageSize, $syncType, $status)
    {
        try {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/lol.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $data = array();
            $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
            $resource=$objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection=$resource->getConnection();
            $tableName=$resource->getTableName('blueshift_cron_activities');
            $query = 'UPDATE '.$tableName.' SET pagesize = "'.$pageSize.'",status="'.$status.'"  WHERE sync_type="'.$syncType.'"';
            $results = $connection->query($query);
        }catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
        return true;
    }

    
    /**
    * @param curlFunc for initialize curl and update data into blueshift app
    */ 
    public function curlFunc($json_data, $path, $method, $password, $key){
        $result = array();
        $ch = curl_init();
        $options = array(
            CURLOPT_URL   => self::CURLURL.$path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_USERPWD =>"$key:$password",
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "cache-control: no-cache",
                "content-type: application/json"
            ),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        );
        curl_setopt_array( $ch, $options );
        $response = curl_exec($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            $result = $err;
        } else {
            $result['status'] =  $httpCode;
            $result['response'] = json_decode($response,true); 
        }
        return $result;
    }
}
