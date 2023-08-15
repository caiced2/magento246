<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget;

/**
 * Class Chooser
 * Cms Pages Hierarchy Grid Block
 *
 * @method \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Chooser setScope(string $value)
 * @method \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Chooser setScopeId(int $value)
 */
class Chooser extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $_nodeFactory;

    /**
     * @var \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Radio
     */
    protected $_widgetRadio;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'hierarchy/widget/chooser.phtml';

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_nodeFactory = $nodeFactory;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_widgetRadio = $this->getLayout()
            ->createBlock(\Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Radio::class);
        return parent::_prepareLayout();
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $uniqueId = $this->mathRandom->getUniqueHash($element->getId());
        $sourceUrl = $this->getUrl('adminhtml/cms_hierarchy_widget/chooser', ['uniq_id' => $uniqueId]);

        $chooser = $this->getLayout()->createBlock(
            \Magento\Widget\Block\Adminhtml\Widget\Chooser::class
        )->setElement(
            $element
        )->setConfig(
            $this->getConfig()
        )->setFieldsetId(
            $this->getFieldsetId()
        )->setSourceUrl(
            $sourceUrl
        )->setUniqId(
            $uniqueId
        );

        if ($element->getValue()) {
            $node = $this->_nodeFactory->create()->load($element->getValue());
            if ($node->getId()) {
                $chooser->setLabel($node->getLabel());
            }
        }

        $radioHtml = $this->_widgetRadio->setUniqId($uniqueId)->toHtml();

        $element->setData('after_element_html', $chooser->toHtml() . $radioHtml);

        return $element;
    }

    /**
     * Retrieve Hierarchy JSON string
     *
     * @return string
     */
    public function getNodesJson()
    {
        return $this->_jsonEncoder->encode($this->getNodes());
    }

    /**
     * Prepare hierarchy nodes for tree building
     *
     * @return array
     */
    public function getNodes()
    {
        /** @var $hierarchyNode \Magento\VersionsCms\Model\Hierarchy\Node */
        $hierarchyNode = $this->_nodeFactory->create();
        $hierarchyNode->setScope($this->getScope());
        $hierarchyNode->setScopeId($this->getScopeId());

        $nodeHeritage = $hierarchyNode->getHeritage();
        unset($hierarchyNode);
        return $nodeHeritage->getNodesData();
    }
}
