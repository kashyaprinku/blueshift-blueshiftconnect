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
class BlueshiftProducts extends AbstractHelper
{
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig , 
        \Magento\Catalog\Model\ProductFactory $productFactory , BlueshiftConfig $blueshiftConfig)
         {
        $this->_scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->blueshiftConfig = $blueshiftConfig;
    }
    public function getProductsTotalCount()
    {
        try {
            $data = array();
            $data['totalCount'] = $this->_scopeConfig->getValue('blueshiftconnect/step2/productcount');
            $data['CurPageSize'] = 40;
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
        return $data;
    }
    public function sendProductsData($step, $CurPageSize){
        try {
            $total_products=$this->_scopeConfig->getValue('blueshiftconnect/step2/productcount');
            $placeholder_image = $this->_scopeConfig->getValue('blueshiftconnect/step2/placeholderimages');
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $baseUrl = $storeManager->getStore()->getBaseUrl();
            if ($total_products == 0)
            {
                $LogMsg ="Products: status = completed";
                $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
                return false;
            }
            $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
            $_product = $objectManager->create('Magento\Catalog\Model\Product')->getCollection()->setPageSize($CurPageSize)->setCurPage($step);
            $products = $_product->getData();
            $i=0;
            $product_data = array();
            foreach ($products as $key => $value) 
            {
                $product = $this->productFactory->create();
                $product_id = (int)$value['entity_id'];
                $childProductId ='';
                if ($value['type_id'] == 'simple')
                 {
                   $product_data['catalog']['products'][$i]['price'] = $product->load($product_id)->getPrice();
                   $Qty = $StockState->getStockQty($product_id, $product->getStore()->getWebsiteId());
                } else if($value['type_id'] == 'downloadable')
                {
                    $product_data['catalog']['products'][$i]['price']=$product->load($product_id)->getPrice();
                    $Qty =0;
                } else if($value['type_id'] == 'bundle')
                {
                    $bundleProduct=$objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
                    $selectionCollection = $bundleProduct->getTypeInstance(true)->getSelectionsCollection( $bundleProduct->getTypeInstance(true)->getOptionsIds($bundleProduct), $bundleProduct );
                    $count=0;
                   
                    foreach ($selectionCollection as $proselection)
                    {
                        if ($count== 1) break;
                            $qyt = $proselection->getSelectionQty();
                            $chilePrice = $proselection->getPrice();
                            $Qty = round($qyt);
                            $childProductId = $proselection->getProductId();
                        $count++;
                    }
                    $product_data['catalog']['products'][$i]['price'] = $chilePrice;
                } else if($value['type_id'] == 'configurable')
                {
                    $configProduct =$objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
                    $product_child = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
                    if(!empty($product_child)){
                        $child = $product_child[0]->getData();
                        $childProductId = $child['entity_id'];
                        $Qty = $StockState->getStockQty($childProductId, $product->getStore()->getWebsiteId());
                        $product_data['catalog']['products'][$i]['price'] = $product->load(intval($childProductId ))->getPrice();
                    }
                    
                } else if($value['type_id'] == 'grouped')
                {
                    $childProductId='';
                    $productObj = $product->load($product_id);
                    $childGrpuped = $productObj->getTypeInstance()->getAssociatedProducts($productObj);
                    if(!empty($childGrpuped))
                    {
                        $childProduct = $childGrpuped[0]->getData();
                        $childId = $childProduct['entity_id']; 
                        $Qty = $StockState->getStockQty($childId, $product->getStore()->getWebsiteId());
                        $product_data['catalog']['products'][$i]['price'] = $product->load((int)$childId)->getPrice();
                        $childProductId = (int)$childId;
                    }
                    
                } else if($value['type_id'] == 'virtual')
                { 
                    $product_data['catalog']['products'][$i]['price'] = $product->load($product_id)->getPrice();
                    $Qty = $StockState->getStockQty($value['entity_id'], $product->getStore()->getWebsiteId());
                }else{
                    $Qty = $this->blueshiftConfig->getProductVisible($ProductId,$Qty);
                }
                if (!empty($childProductId)) 
                {
                    $ProductId = $childProductId;
                } else {
                    $ProductId = $product_id;
                }
                $msrp = $this->blueshiftConfig->getProductMsrp($ProductId);
                $manufacturer = $this->blueshiftConfig->getProductManufacturer($ProductId);
                $product_data['catalog']['products'][$i]['storeUrl']=$baseUrl;
                $product_data['catalog']['products'][$i]['product_type'] = $value['type_id'];
                $product_data['catalog']['products'][$i]['product_id'] = $value['entity_id'];
                $product_data['catalog']['products'][$i]['parent_sku'] = $value['sku'];
                $product_data['catalog']['products'][$i]['web_link'] = $product->load($value['entity_id'])->getProductUrl();
                $product_data['catalog']['products'][$i]['msrp'] = $msrp;
                $product_data['catalog']['products'][$i]['brand'] = $manufacturer;
                if($product->getThumbnail())
                {
                    $product_data['catalog']['products'][$i]['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                } else {
                   $product_data['catalog']['products'][$i]['image'] = $placeholder_image;
                }
                $product_data['catalog']['products'][$i]['title'] = $product->getName();
                $product_data['catalog']['products'][$i]['start_date'] = $value['created_at'];
                $cats = $product->getCategoryIds(); 

                if ($value['type_id'] == 'downloadable')
                 {
                    $product_data['catalog']['products'][$i]['availability'] = "in stock"; 
                } 
                else
                {
                    $product_data['catalog']['products'][$i]['inventory'] = $Qty;
                    if($Qty == 0)
                    {
                        $product_data['catalog']['products'][$i]['availability'] = "out of stock"; 
                    }
                    else
                    {
                        $product_data['catalog']['products'][$i]['availability'] = "in stock"; 
                    }
                }
                if(!empty($cats))
                {
                    foreach ($cats as $key => $cat)
                    {
                        $object_cat = $objectManager->create('Magento\Catalog\Model\Category')->load($cat);
                        $catData =  $object_cat->getData();
                        if(isset($catData['url_path'])){
                        $catPath =  str_replace('/',' > ',$catData['url_path']);
                        $catPath =  str_replace('-',' ',$catPath);
                        }else{
                            $catPath = $catData['name'];
                        }
                        $product_data['catalog']['products'][$i]['category'][]=  ucwords($catPath);  
                    }
                }
            $i++;
            }
            $json_data = json_encode($product_data);
            $userkey=$this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
            $password='';
            $uuid=$this->_scopeConfig->getValue('blueshiftconnect/step2/custom_dropdown');
            $method = "PUT";
            $path = "catalogs/".$uuid.".json";
            $result = $this->blueshiftConfig->curlFunc($json_data, $path, $method, $password, $userkey );
            if($result['status'] != 200)
            {
                $result = json_encode($result);
                $LogMsg = "Products: ".$result.$json_data;
                $this->blueshiftConfig->loggerWrite($LogMsg, 'Blueshift');
            }
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
    }     
}
