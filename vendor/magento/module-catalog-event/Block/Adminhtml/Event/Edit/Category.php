<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\AbstractCategory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\CatalogEvent\Helper\Adminhtml\Event;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;

/**
 * Catalog Events edit form select categories
 */
class Category extends AbstractCategory
{
    /**
     * @var string
     */
    protected $_template = 'categories.phtml';

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var Event
     */
    protected $_eventAdminhtml;

    /**
     * @param Context $context
     * @param Tree $categoryTree
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param EncoderInterface $jsonEncoder
     * @param Event $eventAdminhtml
     * @param array $data
     */
    public function __construct(
        Context $context,
        Tree $categoryTree,
        Registry $registry,
        CategoryFactory $categoryFactory,
        EncoderInterface $jsonEncoder,
        Event $eventAdminhtml,
        array $data = []
    ) {
        $this->_eventAdminhtml = $eventAdminhtml;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
        $this->_jsonEncoder = $jsonEncoder;
    }

    /**
     * Get categories tree as recursive array
     *
     * @param int $parentId
     * @param bool $asJson
     * @param int $recursionLevel
     * @return array
     */
    public function getTreeArray($parentId = null, $asJson = false, $recursionLevel = 3)
    {
        $result = [];
        if ($parentId) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $this->_categoryFactory->create()->load($parentId);
            if ($category->getId()) {
                $tree = $this->_getNodesArray($this->getNode($category, $recursionLevel));
                if (!empty($tree) && !empty($tree['children'])) {
                    $result = $tree['children'];
                }
            }
        } else {
            $result = $this->_getNodesArray($this->getRoot(null, $recursionLevel));
        }
        if ($asJson) {
            return $this->_jsonEncoder->encode($result);
        }
        return $result;
    }

    /**
     * Get categories collection
     *
     * @return Collection
     */
    public function getCategoryCollection()
    {
        $collection = $this->_getData('category_collection');
        if ($collection === null) {
            $collection = $this->_categoryFactory->create()->getCollection()->addAttributeToSelect(
                ['name', 'is_active']
            )->setLoadProductCount(
                true
            );
            $this->setData('category_collection', $collection);
        }
        return $collection;
    }

    /**
     * Convert categories tree to array recursively
     *
     * @param Node $node
     * @return array
     */
    protected function _getNodesArray($node)
    {
        $eventHelper = $this->_eventAdminhtml;
        $result = [
            'id' => (int)$node->getId(),
            'children_count' => (int)$node->getChildrenCount(),
            'disabled' => $node->getLevel() <= 1 || in_array($node->getId(), $eventHelper->getInEventCategoryIds()),
            'name' => $this->_escaper->escapeHtml($node->getName()),
            'product_count' => (int)$node->getProductCount(),
        ];
        if ($node->hasChildren()) {
            $result['children'] = [];
            foreach ($node->getChildren() as $childNode) {
                $result['children'][] = $this->_getNodesArray($childNode);
            }
        }
        $result['cls'] = ($node->getIsActive() ? '' : 'no-') . 'active-category';
        if ($result['disabled']) {
            $result['cls'] .= ' em';
        }
        $result['expanded'] = false;
        if (!empty($result['children'])) {
            $result['expanded'] = true;
        }
        return $result;
    }

    /**
     * Get URL for categories tree ajax loader
     *
     * @return string
     */
    public function getLoadTreeUrl()
    {
        return $this->getUrl('adminhtml/*/categoriesJson');
    }
}
