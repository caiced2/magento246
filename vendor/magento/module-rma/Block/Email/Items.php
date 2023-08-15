<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Email;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Form\Factory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\ModelFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Block\Form;
use Magento\Rma\Helper\Eav;
use Magento\Rma\Model\ResourceModel\Item\Collection;

/**
 * Block for Rma Items in email template
 *
 * @api
 * @since 100.0.2
 */
class Items extends Form
{
    /**
     * Variable to store store-depended string values of attributes
     *
     * @var null|array
     */
    protected $_attributeOptionValues = null;

    /**
     * Rma eav
     *
     * @var Eav
     */
    protected $_rmaEav = null;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @param Context $context
     * @param ModelFactory $modelFactory
     * @param Factory $formFactory
     * @param Config $eavConfig
     * @param Eav $rmaEav
     * @param array $data
     * @param RmaRepositoryInterface|null $rmaRepository
     */
    public function __construct(
        Context $context,
        ModelFactory $modelFactory,
        Factory $formFactory,
        Config $eavConfig,
        Eav $rmaEav,
        array $data = [],
        ?RmaRepositoryInterface $rmaRepository = null
    ) {
        $this->_rmaEav = $rmaEav;
        $this->rmaRepository = $rmaRepository ?? ObjectManager::getInstance()->get(RmaRepositoryInterface::class);
        parent::__construct($context, $modelFactory, $formFactory, $eavConfig, $data);
    }

    /**
     * Get string label of option-type item attributes
     *
     * @param int $attributeValue
     * @return string
     */
    public function getOptionAttributeStringValue($attributeValue)
    {
        if ($this->_attributeOptionValues === null) {
            $this->_attributeOptionValues = $this->_rmaEav->getAttributeOptionStringValues();
        }
        if (isset($this->_attributeOptionValues[$attributeValue])) {
            return $this->_attributeOptionValues[$attributeValue];
        } else {
            return '';
        }
    }

    /**
     * Get collection
     *
     * Custom email templates are only allowed to use scalar values for variable data.
     * So Rma Item Collection is loaded by 'rma_id', which is passed to the block from email template.
     *
     * @return Collection
     * @since 101.2.0
     */
    public function getCollection()
    {
        /** @var RmaInterface $rma */
        $rma = $this->rmaRepository->get($this->getRmaId());
        return $rma->getItemsForDisplay();
    }
}
