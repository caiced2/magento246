<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Hierarchy;

/**
 * Cms Hierarchy Context Menu
 *
 * @api
 * @since 100.0.2
 */
class Menu extends \Magento\Framework\View\Element\Template
{
    const TAG_UL = 'ul';

    const TAG_OL = 'ol';

    const TAG_LI = 'li';

    /**
     * Allowed attributes for UL/OL/LI tags
     *
     * @var array
     */
    protected $_allowedListAttributes = [];

    /**
     * Allowed attributes for A tag
     *
     * @var array
     */
    protected $_allowedLinkAttributes = [];

    /**
     * Allowed attributes for SPAN tag (selected item)
     *
     * @var array
     */
    protected $_allowedSpanAttributes = [];

    /**
     * Total qty nodes in menu
     *
     * @var int
     */
    protected $_totalMenuNodes = 0;

    /**
     * Current Hierarchy Node Page Instance
     *
     * @var \Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $_node;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     *
     * @deprecated 100.1.0 The property can be removed in a future release, when constructor signature can be changed.
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $_nodeFactory;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolverInterface
     */
    private $currentNodeResolver;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory
     * @param array $data
     * @param \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory,
        array $data = [],
        \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver = null
    ) {
        $this->_coreRegistry = $registry;
        $this->_nodeFactory = $nodeFactory;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getNodeId()) {
            $this->_node = $this->_nodeFactory->create()->load($this->getNodeId());
        } else {
            $this->_node = $this->currentNodeResolver->get($this->getRequest());
        }

        $this->_loadNodeMenuParams();

        $this->_allowedListAttributes = [
            'start',
            'value',
            'compact',
            // %attrs
            'id',
            'class',
            'style',
            'title',
            // %coreattrs
            'lang',
            'dir',
            // %i18n
            'onclick',
            'ondblclick',
            'onmousedown',
            'onmouseup',
            'onmouseover',
            'onmousemove',
            'onmouseout',
            'onkeypress',
            'onkeydown',
            'onkeyup',
            // %events
        ];
        $this->_allowedLinkAttributes = [
            'charset',
            'type',
            'name',
            'hreflang',
            'rel',
            'rev',
            'accesskey',
            'shape',
            'coords',
            'tabindex',
            'onfocus',
            'onblur',
            // %attrs
            'id',
            'class',
            'style',
            'title',
            // %coreattrs
            'lang',
            'dir',
            // %i18n
            'onclick',
            'ondblclick',
            'onmousedown',
            'onmouseup',
            'onmouseover',
            'onmousemove',
            'onmouseout',
            'onkeypress',
            'onkeydown',
            'onkeyup',
            // %events
        ];
        $this->_allowedSpanAttributes = [
            'id',
            'class',
            'style',
            'title',
            // %coreattrs
            'lang',
            'dir',
            // %i18n
            'onclick',
            'ondblclick',
            'onmousedown',
            'onmouseup',
            'onmouseover',
            'onmousemove',
            'onmouseout',
            'onkeypress',
            'onkeydown',
            'onkeyup',
            // %events
        ];
    }

    /**
     * Add context menu params to block data
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _loadNodeMenuParams()
    {
        $this->setMenuEnabled(false);

        if ($this->_node instanceof \Magento\Framework\Model\AbstractModel) {
            $params = $this->_node->getMetadataContextMenuParams();
            if ($params !== null && isset($params['menu_visibility']) && $params['menu_visibility'] == 1) {
                $this->addData(
                    [
                        'down' => isset($params['menu_levels_down']) ? $params['menu_levels_down'] : 0,
                        'ordered' => isset($params['menu_ordered']) ? $params['menu_ordered'] : '0',
                        'list_type' => isset($params['menu_list_type']) ? $params['menu_list_type'] : '',
                        'menu_brief' => isset($params['menu_brief']) ? $params['menu_brief'] : '0',
                    ]
                );

                $this->setMenuEnabled(true);
            }
        }
    }

    /**
     * Return menu_brief flag for menu
     *
     * @return bool
     */
    public function isBrief()
    {
        return (bool)$this->_getData('menu_brief');
    }

    /**
     * Retrieve list container TAG
     *
     * @return string
     */
    public function getListContainer()
    {
        $ordered = 1;
        if ($this->hasData('ordered') && $this->getOrdered() !== '') {
            $ordered = $this->getOrdered();
        }
        return (int)$ordered ? self::TAG_OL : self::TAG_UL;
    }

    /**
     * Retrieve List container type attribute
     *
     * @return string
     */
    public function getListType()
    {
        if ($this->hasData('list_type')) {
            $type = $this->_getData('list_type');
            if ($this->getListContainer() == self::TAG_OL) {
                if (in_array($type, ['1', 'A', 'a', 'I', 'i'])) {
                    return $type;
                }
            } elseif ($this->getListContainer() == self::TAG_UL) {
                if (in_array($type, ['disc', 'circle', 'square'])) {
                    return $type;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve Node Replace pairs
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @return array
     */
    protected function _getNodeReplacePairs($node)
    {
        return ['__ID__' => $node->getId(), '__LABEL__' => $node->getLabel(), '__HREF__' => $node->getUrl()];
    }

    /**
     * Retrieve list begin tag
     *
     * @param bool $addStyles Whether to add css styles, type attribute etc. to tag or not
     * @return string
     */
    protected function _getListTagBegin($addStyles = true)
    {
        $templateKey = $addStyles ? '_list_template_styles' : '_list_template';
        $template = $this->_getData($templateKey);

        if (!$template) {
            $template = '<' . $this->getListContainer();

            if ($addStyles) {
                $class = 'cms-menu';

                $type = $this->getListType();
                if ($type) {
                    //$template .= ' type="'.$type.'"';
                    $class .= ' type-' . $type;
                }

                $template .= ' class="' . $class . '"';
            }

            foreach ($this->_allowedListAttributes as $attribute) {
                $value = $this->getData('list_' . $attribute);
                if (!empty($value)) {
                    $template .= ' ' . $attribute . '="' . $this->escapeHtml($value) . '"';
                }
            }
            if ($this->getData('list_props')) {
                $template .= ' ' . $this->getData('list_props');
            }
            $template .= '>';

            $this->setData($templateKey, $template);
        }

        return $template;
    }

    /**
     * Retrieve List end tag
     *
     * @return string
     */
    protected function _getListTagEnd()
    {
        return '</' . $this->getListContainer() . '>';
    }

    /**
     * Retrieve List Item begin tag
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @param bool $hasChilds Whether item contains nested list or not
     * @return string
     */
    protected function _getItemTagBegin($node, $hasChilds = false)
    {
        $templateKey = $hasChilds ? '_item_template_childs' : '_item_template';
        $template = $this->_getData($templateKey);
        if (!$template) {
            $template = '<' . self::TAG_LI;
            if ($hasChilds) {
                $template .= ' class="parent"';
            }
            foreach ($this->_allowedListAttributes as $attribute) {
                $value = $this->getData('item_' . $attribute);
                if (!empty($value)) {
                    $template .= ' ' . $attribute . '="' . $this->escapeHtml($value) . '"';
                }
            }
            if ($this->getData('item_props')) {
                $template .= ' ' . $this->getData('item_props');
            }
            $template .= '>';

            $this->setData($templateKey, $template);
        }

        return strtr($template, $this->_getNodeReplacePairs($node));
    }

    /**
     * Retrieve List Item end tag
     *
     * @return string
     */
    protected function _getItemTagEnd()
    {
        return '</' . self::TAG_LI . '>';
    }

    /**
     * Retrieve Node label with link
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @return string
     */
    protected function _getNodeLabel($node)
    {
        if ($this->_node && $this->_node->getId() == $node->getId()) {
            return $this->_getSpan($node);
        }
        return $this->_getLink($node);
    }

    /**
     * Retrieve Node label with link
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @return string
     */
    protected function _getLink($node)
    {
        $template = $this->_getData('_link_template');
        if (!$template) {
            $template = '<a href="__HREF__"';
            foreach ($this->_allowedLinkAttributes as $attribute) {
                $value = $this->getData('link_' . $attribute);
                if (!empty($value)) {
                    $template .= ' ' . $attribute . '="' . $this->escapeHtml($value) . '"';
                }
            }
            $template .= '><span>__LABEL__</span></a>';
            $this->setData('_link_template', $template);
        }

        return strtr($template, $this->_getNodeReplacePairs($node));
    }

    /**
     * Retrieve Node label for current node
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @return string
     */
    protected function _getSpan($node)
    {
        $template = $this->_getData('_span_template');
        if (!$template) {
            $template = '<strong';
            foreach ($this->_allowedSpanAttributes as $attribute) {
                $value = $this->getData('span_' . $attribute);
                if (!empty($value)) {
                    $template .= ' ' . $attribute . '="' . $this->escapeHtml($value) . '"';
                }
            }
            $template .= '>__LABEL__</strong>';
            $this->setData('_span_template', $template);
        }

        return strtr($template, $this->_getNodeReplacePairs($node));
    }

    /**
     * Retrieve tree slice array
     *
     * @return array
     */
    public function getTree()
    {
        if (!$this->hasData('_tree')) {
            $up = $this->_getData('up');
            if (!abs(intval($up))) {
                $up = 0;
            }
            $down = $this->_getData('down');
            if (!abs(intval($down))) {
                $down = 0;
            }

            $tree = $this->_node->setCollectActivePagesOnly(
                true
            )->setCollectIncludedPagesOnly(
                true
            )->setTreeMaxDepth(
                $down
            )->setTreeIsBrief(
                $this->isBrief()
            )->getTreeSlice(
                $up,
                1
            );

            $this->setData('_tree', $tree);
        }
        return $this->_getData('_tree');
    }

    /**
     * Return total quantity of rendered menu node
     *
     * @return int
     */
    public function geMenuNodesQty()
    {
        return $this->_totalMenuNodes;
    }

    /**
     * Recursive draw menu
     *
     * @param array $tree
     * @param int $parentNodeId
     * @return string
     */
    public function drawMenu(array $tree, $parentNodeId = 0)
    {
        if (!isset($tree[$parentNodeId])) {
            return '';
        }

        $addStyles = $parentNodeId == 0;
        $html = $this->_getListTagBegin($addStyles);

        foreach ($tree[$parentNodeId] as $nodeId => $node) {
            /* @var $node \Magento\VersionsCms\Model\Hierarchy\Node */
            $nested = $this->drawMenu($tree, $nodeId);
            $hasChilds = $nested != '';
            $html .= $this->_getItemTagBegin($node, $hasChilds) . $this->_getNodeLabel($node);
            $html .= $nested;
            $html .= $this->_getItemTagEnd();

            $this->_totalMenuNodes++;
        }

        $html .= $this->_getListTagEnd();

        return $html;
    }

    /**
     * To html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_node || !$this->getMenuEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
}
