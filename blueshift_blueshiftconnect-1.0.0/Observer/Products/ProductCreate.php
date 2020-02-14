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

class ProductCreate implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @param ScopeConfigInterface $scopeConfig,
     * @param Logger $logger,
     * @param ProductRepository $productRepository
     */
    public function __construct(BlueshiftConfig $blueshiftConfig,\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig) {
        $this->_scopeConfig = $scopeConfig;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    /**
     * get data after product creation event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer){ 
        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password = '';
            $uuid=$this->_scopeConfig->getValue('blueshiftconnect/step2/custom_dropdown');
            $placeholder_image = $this->_scopeConfig->getValue('blueshiftconnect/step2/placeholderimages');
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $product = $observer->getProduct();
            $data = array();
            $cats = $product->getCategoryIds(); 
            $product_details = $product->getData();
            $product_id = intval($product_details['entity_id']);
            $childProductId ='';
            $data['catalog']['products'][0]['product_id']=$product_details['entity_id'];
            $data['catalog']['products'][0]['storeUrl']=$baseUrl;
            $data['catalog']['products'][0]['title'] = $product['name'];
            $data['catalog']['products'][0]['parent_sku'] = $product['sku'];
            if($product_details['type_id']== 'simple'){
                $data['catalog']['products'][0]['product_type']=$product_details['type_id'];
                $data['catalog']['products'][0]['price'] = $product_details['price'];
                $Qty = $StockState->getStockQty($product_details['entity_id'], $product->getStore()->getWebsiteId()); 
            }else if($product_details['type_id']== 'configurable'){
                $data['catalog']['products'][0]['product_id']=$product_details['entity_id'];
                $data['catalog']['products'][0]['product_type']=$product_details['type_id'];
                $configProduct=$objectManager->create('Magento\Catalog\Model\Product')->load($product_details['entity_id']);
                $first_child = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
                $childProductId = $first_child[0]->getID();
                $Qty = $StockState->getStockQty($childProductId, $product->getStore()->getWebsiteId());
                $data['catalog']['products'][0]['price'] = $product->load(intval($childProductId ))->getPrice();
                $childProductId = intval($childProductId);
            } elseif($product_details['type_id'] == 'bundle'){
                $bundleProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($product_details['entity_id']);
                $selectionCollection = $bundleProduct->getTypeInstance(true)->getSelectionsCollection( $bundleProduct->getTypeInstance(true)->getOptionsIds($bundleProduct), $bundleProduct );
                $count=0;
                foreach ($selectionCollection as $proselection) {
                    if ($count== 1) break;
                        $qyt = $proselection->getSelectionQty();
                        $chilePrice = $proselection->getPrice();
                        $Qty = round($qyt);
                        $childProductId = $proselection->getProductId();
                    $count++;
                }
                $data['catalog']['products'][0]['price'] = $chilePrice;
            }elseif($product_details['type_id'] == 'grouped'){
                $childProductId='';
                $productObj = $product->load($product_id);
                $childGrpuped = $productObj->getTypeInstance()->getAssociatedProducts($productObj);
                if(!empty($childGrpuped)){
                    $childProduct = $childGrpuped[0]->getData();
                    $childId = $childProduct['entity_id']; 
                    $Qty = $StockState->getStockQty($childId, $product->getStore()->getWebsiteId());
                    $data['catalog']['products'][0]['price'] = $product->load(intval($childId))->getPrice();
                    $childProductId = intval($childId);
                }
                
            }elseif($product_details['type_id'] == 'virtual'){ 
                $data['catalog']['products'][0]['price'] = $product->load($product_id)->getPrice();
                $Qty = $StockState->getStockQty($product_id, $product->getStore()->getWebsiteId());
            }
            if (!empty($childProductId)) {
                $ProductId = $childProductId;
            } else{
                $ProductId = $product_id;
            }
            if($product_details['type_id']== 'downloadable'){
                $data['catalog']['products'][0]['product_type'] = $product_details['type_id'];
                $data['catalog']['products'][0]['price'] = $product_details['price'];
                $data['catalog']['products'][0]['inventory']=0;
                $data['catalog']['products'][0]['availability'] = "in stock";
                $Qty = 1; 
            }
            $data['catalog']['products'][0]['inventory']=$Qty;
            if($Qty == 0){
                $data['catalog']['products'][0]['availability']="out of stock"; 
            }else{
                $data['catalog']['products'][0]['availability']="in stock"; 
            }
            $msrp = $this->blueshiftConfig->getProductMsrp($ProductId);
            $manufacturer = $this->blueshiftConfig->getProductManufacturer($ProductId);
            $Qty = $this->blueshiftConfig->getProductVisible($ProductId,$Qty);
            
            if($product->getThumbnail()){
                $data['catalog']['products'][0]['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
            }else{
               $data['catalog']['products'][0]['image'] = $placeholder_image;
            }
            $title = explode('-', $product['name']);
            $slug = preg_replace('~[^\pL\d]+~u', '-', $title[0]);
            $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
            $slug = preg_replace('~[^-\w]+~', '', $slug);
            $slug = trim($slug, '-');
            $slug = preg_replace('~-+~', '-', $slug);
            $slug = strtolower($slug);
           // $data['catalog']['products'][0]['web_link'] = $store->getBaseUrl().$product_details['url_key'].'.html';
            $data['catalog']['products'][0]['web_link'] = $store->getBaseUrl().$slug.'.html';
            $data['catalog']['products'][0]['msrp'] = $msrp;
            $data['catalog']['products'][0]['brand'] = $manufacturer;
            $data['catalog']['products'][0]['start_date'] = $product['created_at'];
            if(!empty($cats)){
                foreach ($cats as $key => $cat) {
                    $object_cat = $objectManager->create('Magento\Catalog\Model\Category')->load($cat);
                    $cat_data =  $object_cat->getData();
                    if(isset($cat_data['url_path'])){
                    $cat_path =  str_replace('/',' > ',$cat_data['url_path']);
                    $cat_path =  str_replace('-',' ',$cat_path);
                    }else{
                        $cat_path = $cat_data['name'];
                    }
                    $data['catalog']['products'][0]['category'][]=  ucwords($cat_path);  
                }
            }
            $json_data = json_encode($data);
            $path = "catalogs/".$uuid.".json";
            $method = "PUT";
            $result = $this->blueshiftConfig->curlFunc($json_data,$path,$method,$password,$userkey);
            if($result['status']== 200){
                $this->blueshiftConfig->loggerWrite("Product creation: status = ok",'observer');
            }else{
                $result = json_encode($result);
                $this->blueshiftConfig->loggerWrite("Product creation: ".$result,'observer');
            }  
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(),'observer');
        }
    }
}
