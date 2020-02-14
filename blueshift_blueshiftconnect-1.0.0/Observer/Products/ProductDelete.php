<?php 
/**
 * Blueshift Blueshiftconnect Addcart Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Observer\Products;
use Magento\Framework\Event\ObserverInterface;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;

class ProductDelete implements \Magento\Framework\Event\ObserverInterface {
    protected $productRepository;
   // protected $blueshiftconnect;
     /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param BlueshiftConfig $blueshiftConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct( \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,BlueshiftConfig $blueshiftConfig, \Magento\Catalog\Model\ProductRepository $productRepository) {
        $this->_scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $uuid = $this->_scopeConfig->getValue('blueshiftconnect/step2/custom_dropdown');
            $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $placeholder_image = $this->_scopeConfig->getValue('blueshiftconnect/step2/placeholderimages');
            $product = $observer->getEvent()->getProduct();
            $data = array();            
            $cats = $product->getCategoryIds(); 
            $product_details = $product->getData();
            $product_id = $product_details['entity_id'];
            $product_sku = $product_details['sku'];
            $product_data = $this->productRepository->get($product_sku);
            $data['catalog']['products'][0]['title']=$product_data->getName();
            $data['catalog']['products'][0]['product_id']=$product_id;
            $data['catalog']['products'][0]['storeUrl']=$baseUrl;
            $data['catalog']['products'][0]['parent_sku']=$product_sku;
            $data['catalog']['products'][0]['start_date'] = $product['updated_at'];
            if($product_data->getThumbnail()){
                $data['catalog']['products'][0]['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product_data->getThumbnail();
            }else{
               $data['catalog']['products'][0]['image'] = $placeholder_image;
            }
            $data['catalog']['products'][0]['web_link'] = $product_data->getProductUrl();
            $data['catalog']['products'][0]['inventory'] = 0;
            $data['catalog']['products'][0]['availability']="out of stock"; 
        
            $json_data = json_encode($data);  
            $path = "catalogs/".$uuid.".json";
            $method = "PUT";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $this->blueshiftConfig->loggerWrite("Product delete: status = ok",'observer');
            }else{
                $result = json_encode($result);
                $this->blueshiftConfig->loggerWrite("Product delete: ".$result,'observer');
            }  
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'observer');
        }
    }
}
