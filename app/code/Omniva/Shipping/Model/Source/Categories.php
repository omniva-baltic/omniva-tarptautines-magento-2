<?php


namespace Omniva\Shipping\Model\Source;

class Categories implements \Magento\Framework\Option\ArrayInterface
{

    protected $_categories;

    public function __construct(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collection)
    {
        $this->_categories = $collection;
    }

    public function toOptionArray()
    {

        $collection = $this->_categories->create();
        $collection->addAttributeToSelect('*')->addFieldToFilter('is_active', 1);
        $options = [];
        foreach ($collection as $category) {
            $options[] = ['value' => $category->getId(), 'label' => $category->getName()];
        }
        return $options;
    }

}