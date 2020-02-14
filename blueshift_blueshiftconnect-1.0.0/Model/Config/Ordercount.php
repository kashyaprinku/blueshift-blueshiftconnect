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
class Ordercount implements ArrayInterface {
	public function toOptionArray() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$count = $objectManager->get('Magento\Sales\Model\Order')->getCollection()->getSize(); 
    	$ret = [];
        $ret[] = ['value' => $count, 'selected' => 'selected', 'label' => $count,];
    	return $ret;
	}
}