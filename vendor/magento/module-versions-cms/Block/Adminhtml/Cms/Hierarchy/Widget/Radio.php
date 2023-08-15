<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget;

/**
 * Cms Pages Hierarchy Widget Radio Block
 */
class Radio extends \Magento\Backend\Block\Template
{
    /**
     * Unique Hash Id
     *
     * @var null
     */
    protected $_uniqId = null;

    /**
     * Widget Parameters
     *
     * @var array
     */
    protected $_params = [];

    /**
     * All Store Views
     *
     * @var array
     */
    protected $_allStoreViews = [];

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'hierarchy/widget/radio.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $_hierarchyNode;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $hierarchyNode
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\VersionsCms\Model\Hierarchy\Node $hierarchyNode,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_hierarchyNode = $hierarchyNode;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $data);
    }

    /**
     * Get all Store View labels and ids
     *
     * @return array
     */
    public function getAllStoreViews()
    {
        if (empty($this->_allStoreViews)) {
            $storeValues = $this->_systemStore->getStoreValuesForForm(false, true);
            foreach ($storeValues as $view) {
                if (is_array($view['value']) && empty($view['value'])) {
                    continue;
                }
                if ($view['value'] == 0) {
                    $view['value'] = [['label' => $view['label'], 'value' => $view['value']]];
                }
                foreach ($view['value'] as $store) {
                    $this->_allStoreViews[] = $store;
                }
            }
        }

        return $this->_allStoreViews;
    }

    /**
     * Get array with Store View labels and ids
     *
     * @return array
     */
    public function getAllStoreViewsList()
    {
        $allStoreViews = $this->getAllStoreViews();
        reset($allStoreViews);
        $storeViews[] = current($allStoreViews);
        unset($allStoreViews);

        $storeValues = $this->_systemStore->getStoreCollection();

        foreach ($storeValues as $store) {
            $storeViews[] = ['label' => $store->getName(), 'value' => $store->getId()];
        }

        return $storeViews;
    }

    /**
     * Get All Store Views Ids array
     *
     * @return array
     */
    public function getAllStoreViewIds()
    {
        $ids = [];
        foreach ($this->getAllStoreViews() as $view) {
            $ids[] = $view['value'];
        }

        return $ids;
    }

    /**
     * Get Unique Hash
     *
     * @return null|string
     */
    public function getUniqHash()
    {
        if ($this->getUniqId() !== null) {
            $id = explode('_', $this->getUniqId());
            if (isset($id[1])) {
                return $id[1];
            }
        }
        return null;
    }

    /**
     * Get Widget Parameters
     *
     * @return array
     */
    public function getParameters()
    {
        if (empty($this->_params)) {
            $this->_params = [];
            $widget = $this->_coreRegistry->registry('current_widget_instance');
            $block = $this->getLayout()->getBlock('wysiwyg_widget.options');
            if ($widget) {
                $this->_params = $widget->getWidgetParameters();
            } elseif ($block) {
                $this->_params = $block->getWidgetValues();
            }
        }
        return $this->_params;
    }

    /**
     * Get Parameter Value
     *
     * @param int $key
     * @return string
     */
    public function getParamValue($key)
    {
        $params = $this->getParameters();

        return isset($params[$key]) ? $params[$key] : '';
    }

    /**
     * Get Label Value By Node Id
     *
     * @param int $nodeId
     * @return string
     */
    public function getLabelByNodeId($nodeId)
    {
        if ($nodeId) {
            $node = $this->_hierarchyNode->load($nodeId);
            if ($node->getId()) {
                return $node->getLabel();
            }
        }
        return '';
    }

    /**
     * Retrieve block HTML markup
     *
     * @return string
     */
    protected function _toHtml()
    {
        return $this->_storeManager->isSingleStoreMode() == false ? parent::_toHtml() : '';
    }
}
