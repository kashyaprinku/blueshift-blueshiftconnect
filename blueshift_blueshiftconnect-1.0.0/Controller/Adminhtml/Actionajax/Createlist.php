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
use Magento\Framework\Json\Helper\Data as JsonHelperData;
use Magento\Framework\App\Bootstrap;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
/**
* API for creating a catalog list in blueshift
*
* @param execute
*/
class Createlist extends \Magento\Backend\App\Action
{
    /**
    * @param ScopeConfigInterface $scopeConfig,
    * @param BlueshiftConfig $blueshiftConfig
    * @param ProductRepository $productRepository,
    */
    public function __construct(Context $context , \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig , BlueshiftConfig $blueshiftConfig) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    public function execute()
    {
        try {
            $response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $listname = filter_input(INPUT_POST, "listname");
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $json_data='{"catalog" : {"name" : "'.$listname.'"}}'; 
            $path = "catalogs";
            $method = "POST";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200)
            {
                $response->setContents(json_encode($result['response']));
                $this->blueshiftConfig->loggerWrite("Create Catalog: status = ok",'Blueshift');
            } else {
                $result =json_encode($result);
                $response->setContents('{"catalog_uuid":"Something went wrong."}');
                $logMsg ='{"Create Catalog":"error", "response":"'.$result.'"}';
                $this->blueshiftConfig->loggerWrite($logMsg,'Blueshift');
            }
            return $response; 
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'Blueshift');
        }
    }     
}
