<?php
/**
 * Blueshift Blueshiftconnect total product count 
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Model\Config;
use Magento\Framework\Option\ArrayInterface;
class Productcount implements ArrayInterface{
	protected $collectionFactory;
	public function __construct(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory) {
        $this->collectionFactory = $collectionFactory;
	}
	public function toOptionArray(){
    	$collection = $this->collectionFactory->create();
    	$count = $collection->getSize(); 
    	$ret = [];
        $ret[] = ['value' => $count, 'selected' => 'selected', 'label' => $count,];
    	return $ret;   
	}
}