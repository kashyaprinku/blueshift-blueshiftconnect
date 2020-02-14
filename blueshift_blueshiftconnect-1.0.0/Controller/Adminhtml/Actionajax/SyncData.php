<?php
/**
* Blueshift Blueshiftconnect
* @category  Blueshift
* @package   Blueshift_Blueshiftconnect
* @author    Blueshift
* @copyright Copyright (c) Blueshift(https://blueshift.com/)
*/
namespace Blueshift\Blueshiftconnect\Controller\Adminhtml\Actionajax;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Json\Helper\Data as JsonHelperData;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
use Blueshift\Blueshiftconnect\Helper\BlueshiftCustomers as BlueshiftCustomers;
use Blueshift\Blueshiftconnect\Helper\BlueshiftProducts as BlueshiftProducts;
use Blueshift\Blueshiftconnect\Helper\BlueshiftOrders as BlueshiftOrders;
use Blueshift\Blueshiftconnect\Helper\BlueshiftAbandonedCarts as BlueshiftAbandonedCarts;
class syncData extends \Magento\Backend\App\Action
{
    public function __construct(Context $context ,\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\Config\Storage\WriterInterface $configWriter , BlueshiftConfig $blueshiftConfig , BlueshiftCustomers $blueshiftCustomers , BlueshiftOrders $blueshiftOrders , BlueshiftProducts $blueshiftProducts , BlueshiftAbandonedCarts $blueshiftAbandonedCarts ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->blueshiftConfig = $blueshiftConfig;
        $this->blueshiftCustomers = $blueshiftCustomers;
        $this->blueshiftProducts = $blueshiftProducts;
        $this->blueshiftOrders = $blueshiftOrders;
        $this->blueshiftAbandonedCarts = $blueshiftAbandonedCarts;
    }
    public function execute()
    {
        try{    
            $syncStstus = $this->_scopeConfig->getValue('blueshiftconnect/step2/synchronizationststus');
            $step1 = filter_input(INPUT_POST , "step");
            $customersData = $this->blueshiftCustomers->getCustomersTotalCount();
            $totalCustomers = $customersData['totalCount'];
            $customerPageSize = $customersData['CurPageSize'];
            $customerStep = ceil($totalCustomers / $customerPageSize);
            $LogMsg ="One time Synchronization start";
            $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
            if($customerStep != 0)
            {
                for($step = $step1; $step <= $customerStep;  $step++)
                {   
                    $this->blueshiftCustomers->sendCustomersData($step , $customerPageSize);
                    $this->_configWriter->save('blueshiftconnect/step2/synchronizationststus' , 'In Progress' , 'default' , 0);
                    if($step == 1)
                    {
                        $CusMsg ="Customers In Progress";
                        $this->blueshiftConfig->loggerWrite($CusMsg,'Blueshift');
                    }
                }
            }
            $productsData = $this->blueshiftProducts->getProductsTotalCount();
            $totalproducts = $productsData['totalCount'];
            $productPageSize = $productsData['CurPageSize'];
            $productsStep = ceil($totalproducts / $productPageSize);
            if($productsStep != 0)
            {
                for($step = 1; $step <= $productsStep; $step++)
                {
                    $this->blueshiftProducts->sendProductsData($step,$productPageSize);
                    $this->_configWriter->save('blueshiftconnect/step2/synchronizationststus', 'In Progress', 'default', 0);
                    if($step == 1)
                    {
                        $LogMsg ="Products In Progress";
                        $this->blueshiftConfig->loggerWrite($LogMsg, 'Blueshift');
                    }
                }
            }
            $orderData = $this->blueshiftOrders->getOrdersTotalCount();
            $totalOrders = $orderData['totalCount'];
            $orderPageSize = $orderData['CurPageSize'];
            $ordersStep = ceil($totalOrders / $orderPageSize);
            if($ordersStep != 0)
            {
                for($step = 1; $step <= $ordersStep; $step++)
                {
                    $this->blueshiftOrders->sendOrdersData($step,$orderPageSize);
                    $this->_configWriter->save('blueshiftconnect/step2/synchronizationststus', 'In Progress', 'default', 0);
                    if($step == 1)
                    {
                        $LogMsg ="Order In Progress";
                        $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
                    }
                }
            }
            $AbandonedcartsData = $this->blueshiftAbandonedCarts->getAbandonedcartsTotalCount();
            $totalAbandonedcart = $AbandonedcartsData['totalCount'];
            $AbandonedcartPageSize = $AbandonedcartsData['CurPageSize'];
            $AbandonedCartStep = ceil($totalAbandonedcart / $AbandonedcartPageSize);
            if($AbandonedCartStep != 0)
            {
                for($step = 1; $step <= $AbandonedCartStep; $step++)
                {
                    $this->blueshiftAbandonedCarts->sendAbandonedcartsData($step, $AbandonedcartPageSize);
                    if($step == 1)
                    {
                        $LogMsg ="Abandonedcart In Progress";
                        $this->blueshiftConfig->loggerWrite($LogMsg,'Blueshift');
                    }
                    if($step == $AbandonedCartStep){
                        $this->_configWriter->save('blueshiftconnect/step2/synchronizationststus', 'Complete','default', 0);
                        $LogMsg ="One time Synchronization Completed";
                        $this->blueshiftConfig->loggerWrite($LogMsg, 'Blueshift');
                    }
                }
            }
            if($syncStstus != 'Complete'){
                $this->_configWriter->save('blueshiftconnect/step2/synchronizationststus', 'Complete','default', 0);
            }
        }catch (\Exception $e) {
            $this->blueshiftConfig->loggerWrite($e->getMessage(), 'Blueshift');
        }  
    }     
}
