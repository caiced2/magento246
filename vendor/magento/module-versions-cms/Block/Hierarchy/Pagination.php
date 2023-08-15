<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Hierarchy;

/**
 * Cms Widget Pagination Block
 *
 * @api
 * @since 100.0.2
 */
class Pagination extends \Magento\Framework\View\Element\Template
{
    /**
     * Current Hierarchy Node Page Instance
     *
     * @var \Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $node;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     *
     * @deprecated 100.1.0 The property can be removed in a future release, when constructor signature can be changed.
     */
    protected $registry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var array
     */
    protected $pagerFlags = [
        'flags' => ['previous' => false, 'next' => false],
        'count' => 0,
        'previous' => null,
        'next' => null,
        'first' => null,
        'last' => null,
        'current' => 0
    ];

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
        $this->registry = $registry;
        $this->nodeFactory = $nodeFactory;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Define default template and settings
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getNodeId()) {
            $this->node = $this->nodeFactory->create()->load($this->getNodeId());
        } else {
            $this->node = $this->currentNodeResolver->get($this->getRequest());
        }

        $this->setData('sequence', 1);
        $this->setData('outer', 1);
        $this->setData('frame', 10);
        $this->setData('jump', 0);
        $this->setData('use_node_labels', 0);

        $this->_loadNodePaginationParams();
    }

    /**
     * Add context menu params to block data
     *
     * @return void
     */
    protected function _loadNodePaginationParams()
    {
        $this->setPaginationEnabled(false);

        if ($this->node instanceof \Magento\Framework\Model\AbstractModel) {
            $params = $this->node->getMetadataPagerParams();
            if ($params !== null && isset(
                $params['pager_visibility']
            ) && $params['pager_visibility'] == \Magento\VersionsCms\Helper\Hierarchy::METADATA_VISIBILITY_YES
            ) {
                $this->addData(
                    [
                        'jump' => isset($params['pager_jump']) ? $params['pager_jump'] : 0,
                        'frame' => isset($params['pager_frame']) ? $params['pager_frame'] : 0,
                    ]
                );

                $this->setPaginationEnabled(true);
            }
        }
    }

    /**
     * Use Node label instead of numeric pages
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseNodeLabels()
    {
        return $this->_getData('use_node_labels') > 0;
    }

    /**
     * Can show Previous and Next links
     *
     * @return bool
     */
    public function canShowSequence()
    {
        return $this->_getData('sequence') > 0;
    }

    /**
     * Can show First and Last links
     *
     * @return bool
     */
    public function canShowOuter()
    {
        return $this->getJump() > 0 && $this->_getData('outer') > 0;
    }

    /**
     * Retrieve how many links to pages to show as one frame in the pagination widget.
     *
     * @return int
     */
    public function getFrame()
    {
        return abs(intval($this->_getData('frame')));
    }

    /**
     * Retrieve whether to show link to page number current + y
     * that extends frame size if applicable
     *
     * @return int
     */
    public function getJump()
    {
        return abs(intval($this->_getData('jump')));
    }

    /**
     * Retrieve node label or number
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @param string $custom instead of page number
     * @return string
     */
    public function getNodeLabel(\Magento\VersionsCms\Model\Hierarchy\Node $node, $custom = null)
    {
        if ($this->getUseNodeLabels()) {
            return $node->getLabel();
        }
        if ($custom !== null) {
            return $custom;
        }
        return $node->getPageNumber();
    }

    /**
     * Can show First page
     *
     * @return bool
     */
    public function canShowFirst()
    {
        return $this->getCanShowFirst();
    }

    /**
     * Retrieve First node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getFirstNode()
    {
        return $this->_getData('first_node');
    }

    /**
     * Can show Last page
     *
     * @return bool
     */
    public function canShowLast()
    {
        return $this->getCanShowLast();
    }

    /**
     * Retrieve First node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getLastNode()
    {
        return $this->_getData('last_node');
    }

    /**
     * Can show Previous  page link
     *
     * @return bool
     */
    public function canShowPrevious()
    {
        return $this->getPreviousNode() !== null;
    }

    /**
     * Retrieve Previous  node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getPreviousNode()
    {
        return $this->_getData('previous_node');
    }

    /**
     * Can show Next page link
     *
     * @return bool
     */
    public function canShowNext()
    {
        return $this->getNextNode() !== null;
    }

    /**
     * Retrieve Next node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getNextNode()
    {
        return $this->_getData('next_node');
    }

    /**
     * Can show Previous Jump page link
     *
     * @return bool
     */
    public function canShowPreviousJump()
    {
        return $this->getJump() > 0 && $this->getCanShowPreviousJump();
    }

    /**
     * Retrieve Previous Jump node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getPreviousJumpNode()
    {
        return $this->_getData('previous_jump');
    }

    /**
     * Can show Next Jump page link
     *
     * @return bool
     */
    public function canShowNextJump()
    {
        return $this->getJump() > 0 && $this->getCanShowNextJump();
    }

    /**
     * Retrieve Next Jump node page
     *
     * @return \Magento\VersionsCms\Model\Hierarchy\Node
     */
    public function getNextJumpNode()
    {
        return $this->_getData('next_jump');
    }

    /**
     * Is Show Previous and Next links
     *
     * @return bool
     */
    public function isShowOutermost()
    {
        return $this->_getData('outermost') > 1;
    }

    /**
     * Initialize and set flags for pagination into class fields
     *
     * @param array $nodes
     * @return void
     */
    protected function initPagerFlags(array $nodes)
    {
        $this->pagerFlags['count'] = count($nodes);

        foreach ($nodes as $k => $node) {
            $node->setPageNumber($k + 1);
            $node->setIsCurrent(false);
            if ($this->pagerFlags['first'] === null) {
                $this->pagerFlags['first'] = $node;
            }
            if ($this->pagerFlags['flags']['next']) {
                $this->pagerFlags['next'] = $node;
                $this->pagerFlags['flags']['next'] = false;
            }
            if ($node->getId() == $this->node->getId()) {
                $this->pagerFlags['flags']['next'] = true;
                $this->pagerFlags['flags']['previous'] = true;
                $this->pagerFlags['current'] = $k;
                $node->setIsCurrent(true);
            }
            if (!$this->pagerFlags['flags']['previous']) {
                $this->pagerFlags['previous'] = $node;
            }
            $this->pagerFlags['last'] = $node;
        }
    }

    /**
     * Calculate and get frame range
     *
     * @return array
     */
    protected function calculatePagesFrameRange()
    {
        if ($this->getFrame() > 0) {
            $middleFrame = ceil($this->getFrame() / 2);

            if ($this->pagerFlags['count'] >= $this->getFrame() && $this->pagerFlags['current'] < $middleFrame) {
                $start = 0;
            } else {
                $start = $this->pagerFlags['current'] - $middleFrame + 1;
                if ($start + 1 + $this->getFrame() > $this->pagerFlags['count']) {
                    $start = $this->pagerFlags['count'] - $this->getFrame();
                }
            }
            if ($start > 0) {
                $this->setCanShowFirst(true);
            } else {
                $this->setCanShowFirst(false);
            }
            $end = $start + $this->getFrame();
            if ($end < $this->pagerFlags['count']) {
                $this->setCanShowLast(true);
            } else {
                $this->setCanShowLast(false);
            }
        } else {
            $this->setCanShowFirst(false);
            $this->setCanShowLast(false);
            $start = 0;
            $end = $this->pagerFlags['count'];
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * Calculate and set data for jump buttons
     *
     * @param array $nodes
     * @param array $frameRange
     * @return void
     */
    protected function calculateAndInitJump(array $nodes, array $frameRange)
    {
        $this->setCanShowPreviousJump(false);
        $this->setCanShowNextJump(false);
        if ($frameRange['start'] > 1) {
            $this->setCanShowPreviousJump(true);
            if ($frameRange['start'] - 1 > $this->getJump() * 2) {
                $jump = $frameRange['start'] - $this->getJump();
            } else {
                $jump = ceil(($frameRange['start'] - 1) / 2);
            }
            $this->setPreviousJump($nodes[$jump]);
        }
        if ($this->pagerFlags['count'] - 1 > $frameRange['end']) {
            $this->setCanShowNextJump(true);
            $difference = $this->pagerFlags['count'] - $frameRange['end'] - 1;
            if ($difference < $this->getJump() * 2) {
                $jump = $frameRange['end'] + ceil($difference / 2) - 1;
            } else {
                $jump = $frameRange['end'] + $this->getJump() - 1;
            }
            $this->setNextJump($nodes[$jump]);
        }
    }

    /**
     * Retrieve Nodes collection array
     *
     * @return array
     */
    public function getNodes()
    {
        if (!$this->hasData('_nodes')) {
            // initialize nodes
            $nodes = $this->node->setCollectActivePagesOnly(true)->getParentNodeChildren();

            $this->initPagerFlags($nodes);

            $this->setPreviousNode($this->pagerFlags['previous']);
            $this->setFirstNode($this->pagerFlags['first']);
            $this->setLastNode($this->pagerFlags['last']);
            $this->setNextNode($this->pagerFlags['next']);
            $this->setCanShowNext($this->pagerFlags['next'] !== null);

            $frameRange = $this->calculatePagesFrameRange();

            $this->calculateAndInitJump($nodes, $frameRange);

            $this->setRangeStart($frameRange['start']);
            $this->setRangeEnd($frameRange['end']);

            $this->setData('_nodes', $nodes);
        }
        return $this->_getData('_nodes');
    }

    /**
     * Retrieve nodes in range
     *
     * @return array
     */
    public function getNodesInRange()
    {
        $range = [];
        $nodes = $this->getNodes();
        foreach ($nodes as $k => $node) {
            if ($k >= $this->getRangeStart() && $k < $this->getRangeEnd()) {
                $range[] = $node;
            }
        }
        return $range;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->node || !$this->getPaginationEnabled()) {
            return '';
        }

        // collect nodes to output pagination in template
        $nodes = $this->getNodes();

        // don't display pagination with one page
        if (count($nodes) <= 1) {
            return '';
        }

        return parent::_toHtml();
    }
}
