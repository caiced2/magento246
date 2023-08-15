<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Widget;

/**
 * Cms Hierarchy Node Widget Block
 */
class Node extends \Magento\Framework\View\Element\Html\Link implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Current Hierarchy Node Page Instance
     *
     * @var \Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $node;

    /**
     * Current Store Id
     *
     * @var int
     */
    protected $storeId;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     *
     * @deprecated 100.1.0 The property can be removed in a future release, when constructor signature can be changed.
     */
    protected $coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $hierarchyNodeFactory;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolverInterface
     */
    private $currentNodeResolver;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param array $data
     * @param \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        array $data = [],
        \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver = null
    ) {
        $this->coreRegistry = $registry;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Internal constructor, that is called from real constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $template = $this->getInstanceData('template');
        if ($template) {
            $this->setTemplate($template);
        }
    }

    /**
     * Retrieve specified anchor text
     *
     * @return string
     */
    public function getLabel()
    {
        $value = $this->getInstanceData('anchor_text');
        return $value !== false && !empty($value) ? $value : $this->node->getLabel();
    }

    /**
     * Retrieve link specified title
     *
     * @return string
     */
    public function getTitle()
    {
        $value = $this->getInstanceData('title');
        return $value !== false && !empty($value) ? $value : $this->node->getLabel();
    }

    /**
     * Retrieve Node ID
     *
     * @return mixed
     */
    public function getNodeId()
    {
        return $this->getInstanceData('node_id');
    }

    /**
     * Retrieve Node URL
     *
     * @return string
     */
    public function getHref()
    {
        return $this->node->getUrl();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getNodeId()) {
            $this->node = $this->hierarchyNodeFactory->create()->load($this->getNodeId());
        } else {
            $this->node = $this->currentNodeResolver->get($this->getRequest());
        }

        if (!$this->node) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve Store Id
     *
     * @return int
     */
    protected function getStoreId()
    {
        if (null === $this->storeId) {
            $this->storeId = $this->_storeManager->getStore()->getId();
        }
        return $this->storeId;
    }

    /**
     * Retrieve data from instance
     *
     * @param string $key
     * @return mixed
     */
    protected function getInstanceData($key)
    {
        $dataKeys = [
            $key . '_' . $this->getStoreId(),
            $key . '_' . \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            $key,
        ];
        foreach ($dataKeys as $value) {
            if ($this->getData($value) !== null) {
                return $this->getData($value);
            }
        }
        return false;
    }
}
