<?php
/**
* Blueshift Blueshiftconnect Addcart Observer
* @category  Blueshift
* @package   Blueshift_Blueshiftconnect
* @author    Blueshift
* @copyright Copyright (c) Blueshift(https://blueshift.com/)
*/
namespace Blueshift\Blueshiftconnect\Block\System\Config;
use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
class DatePicker extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_coreRegistry;
    /**
     * Checkbox constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(Context $context , Registry $coreRegistry , array $data = []) 
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context , $data);
    }
    /**
    * Retrieve element HTML markup.
    * @param AbstractElement $element
    * @return string
    */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $element->getElementHtml();
        if (!$this->_coreRegistry->registry('datepicker_loaded')) {
            $this->_coreRegistry->registry('datepicker_loaded' , 1);
        }
        $html .= '<button type="button" style="display:none;" class="ui-datepicker-trigger '
            .'v-middle"><span>Select Date</span></button>';
        $html .= '<script type="text/javascript">
            require(["jquery", "jquery/ui"], function (jq) {
                jq(document).ready(function () {
                    jq("#' . $element->getHtmlId() . '").datepicker( { dateFormat: "yy-mm-dd" } );
                    jq(".ui-datepicker-trigger").removeAttr("style");
                    jq(".ui-datepicker-trigger").click(function(){
                        jq("#' . $element->getHtmlId() . '").focus();
                    });
                });
            });
            </script>';
        return $html;
    }
}