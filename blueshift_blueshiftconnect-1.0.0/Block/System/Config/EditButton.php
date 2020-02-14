<?php
/**
* Blueshift Blueshiftconnect Edit button
* @category  Blueshift
* @package   Blueshift_Blueshiftconnect
* @author    Blueshift
* @copyright Copyright (c) Blueshift(https://blueshift.com/)
*/
namespace Blueshift\Blueshiftconnect\Block\System\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Blueshift\Blueshiftconnect\Helper\BlueshiftConfig as BlueshiftConfig;
class EditButton extends Field 
{
    protected $_template = 'Blueshift_Blueshiftconnect::system/config/editbutton.phtml';
    /**
    * Checkbox constructor.
    * @param \Magento\Backend\Block\Template\Context $context
    * @param array $data
    */
    public function __construct(\Magento\Framework\App\Config\Storage\WriterInterface $configWriter ,BlueshiftConfig $blueshiftConfig , Context $context , array $data = []
    ) {
        parent::__construct($context, $data);
        $this->blueshiftConfig = $blueshiftConfig;
        $this->_configWriter = $configWriter;
    }
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
    /**
     * Retrieve element HTML markup.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    public function getCustomUrl()
    {
        return $this->getUrl('router/controller/action');
    }
    public function getPlaceholderImage()
    {
        return $this->getViewFileUrl('Blueshift_Blueshiftconnect::images/thumbnail.jpg');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData([  'id' => 'edit_btn_id', 'label' => __('Edit'), ] );
        return $button->toHtml();
    }
    public function getSyncActivityStatusFun()
    {
        $result = $this->blueshiftConfig->getSyncActivityStatus();
        return $result;
    }
}