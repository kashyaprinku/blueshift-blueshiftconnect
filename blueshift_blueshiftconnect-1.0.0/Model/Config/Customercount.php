<?php
/**
 * Blueshift Blueshiftconnect Addcart Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Model\Config;
use Magento\Framework\Option\ArrayInterface;
class Customercount implements ArrayInterface {
	public function toOptionArray() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$count = $objectManager->create('Magento\Customer\Model\Customer')->getCollection()->getSize(); 
    	$ret = [];
        $ret[] = ['value' => $count,'selected' => 'selected', 'label' => $count,];
    	return $ret;
	}
}