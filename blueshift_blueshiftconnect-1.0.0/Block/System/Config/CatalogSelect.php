<?php
/**
* Blueshift Blueshiftconnect Addcart Observer
* @category  Blueshift
* @package   Blueshift_Blueshiftconnect
* @author    Blueshift
* @copyright Copyright (c) Blueshift(https://blueshift.com/)
*/
namespace Blueshift\Blueshiftconnect\Block\System\Config;
class CatalogSelect implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Checkbox constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }
    public function toOptionArray()
    {
        $userkey = $this->_scopeConfig->getValue('blueshiftconnect/Step1/userapikey');
   		$password = "";
        $url = 'https://api.getblueshift.com/api/v1/';
        $ch = curl_init();
        $options = array(
            CURLOPT_URL            => $url."catalogs",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_USERPWD =>"$userkey:$password",
            CURLOPT_CUSTOMREQUEST => "GET",
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
        $list_array = [];
        $list_array[0]['value'] = 0;
        $list_array[0]['label'] = "Select Catalog";
        if($httpCode==200)
        {
	  		$obj = json_decode($response);
	  		$i = 1;
            if(!empty($obj))
            {
		  		foreach ($obj as $key => $value) 
                {
		  			$list_array[$i]['value'] = $value->uuid;
		  			$list_array[$i]['label'] = $value->name;
		  			$i++;
		  		}
            }
		}
	return $list_array;
   }
}