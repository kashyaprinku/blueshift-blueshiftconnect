<?php
/**
 * Blueshift Blueshiftconnect Addcart Observer
 * @category  Blueshift
 * @package   Blueshift_Blueshiftconnect
 * @author    Blueshift
 * @copyright Copyright (c) Blueshift(https://blueshift.com/)
 */
namespace Blueshift\Blueshiftconnect\Setup;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Config\Model\ResourceModel\Config\Data;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
/**
 * @codeCoverageIgnore
 */
class Uninstall implements UninstallInterface
{
    protected $collectionFactory;
    protected $configResource;
    /**
     * @param CollectionFactory $collectionFactory
     * @param Data $configResource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Data $configResource
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->configResource    = $configResource;
    }
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    // @codingStandardsIgnoreEnd
    {
        //remove config settings if any
        $collection = $this->collectionFactory->create()
            ->addPathFilter('blueshiftconnect');
        foreach ($collection as $config) {
            $this->deleteConfig($config);
        }
    }

    /**
     * @param AbstractModel $config
     * @throws \Exception
     */
    protected function deleteConfig(AbstractModel $config)
    {
        $this->configResource->delete($config);
    }
}