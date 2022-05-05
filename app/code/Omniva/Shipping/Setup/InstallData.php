<?php
namespace Omniva\Shipping\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InstallData implements InstallDataInterface
{
 protected $eav_setup;
 protected $connection;
 public function __construct(EavSetupFactory $eavSetupFactory,
    \Magento\Framework\App\ResourceConnection $connection,
    \Magento\Eav\Model\Config $eavConfig
 ) {
     $this->eav_setup_factory = $eavSetupFactory;
     $this->connection = $connection->getConnection();
     $this->eav_config = $eavConfig;
 }
 public function install(
    ModuleDataSetupInterface $setup,
    ModuleContextInterface $context
 ) {
    $setup->startSetup();

    //create Category Attributes
    $this->createCategoryAttributes($setup);

    $setup->endSetup();
 }
 protected function createCategoryAttributes($setup)
 {
    $eav_setup = $this->eav_setup_factory->create(['setup' => $setup]);
    $eav_setup->addAttribute(
        \Magento\Catalog\Model\Category::ENTITY,
        'product_weight',
        [
            'type' => 'text',
            'label' => 'Weight, kg',
            'input' => 'text',
            'sort_order' => 420,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'Default product parameters',
            'backend' => ''
        ]
    );
    $eav_setup->addAttribute(
        \Magento\Catalog\Model\Category::ENTITY,
        'product_length',
        [
            'type' => 'text',
            'label' => 'Length, cm',
            'input' => 'text',
            'sort_order' => 420,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'Default product parameters',
            'backend' => ''
        ]
    );
    $eav_setup->addAttribute(
        \Magento\Catalog\Model\Category::ENTITY,
        'product_width',
        [
            'type' => 'text',
            'label' => 'Width, cm',
            'input' => 'text',
            'sort_order' => 420,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'Default product parameters',
            'backend' => ''
        ]
    );
    $eav_setup->addAttribute(
        \Magento\Catalog\Model\Category::ENTITY,
        'product_height',
        [
            'type' => 'text',
            'label' => 'Height, cm',
            'input' => 'text',
            'sort_order' => 420,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'Default product parameters',
            'backend' => ''
        ]
    );
 }
}