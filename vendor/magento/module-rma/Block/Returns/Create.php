<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Returns;

use Magento\Rma\Model\Item\Attribute;
use Magento\Sales\Model\Order\Item;

/**
 * Block class for the return-create page
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Create extends \Magento\Rma\Block\Form
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Rma item factory
     *
     * @var \Magento\Rma\Model\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Rma item form factory
     *
     * @var \Magento\Rma\Model\Item\FormFactory
     */
    protected $_itemFormFactory;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * Constructor of class Create
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Collection\ModelFactory $modelFactory
     * @param \Magento\Eav\Model\Form\Factory $formFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Rma\Model\ItemFactory $itemFactory
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Collection\ModelFactory $modelFactory,
        \Magento\Eav\Model\Form\Factory $formFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Rma\Model\ItemFactory $itemFactory,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        array $data = []
    ) {
        $data['rmaHelper'] = $rmaData;
        $this->_coreRegistry = $registry;
        $this->_rmaData = $rmaData;
        $this->_itemFactory = $itemFactory;
        $this->_itemFormFactory = $itemFormFactory;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $modelFactory, $formFactory, $eavConfig, $data);
    }

    /**
     * Initialize current order
     *
     * @return void
     */
    public function _construct()
    {
        $order = $this->_coreRegistry->registry('current_order');
        if (!$order) {
            return;
        }
        $this->setOrder($order);

        $items = $this->_rmaData->getOrderItems($order);
        $this->setItems($items);

        $formData = $this->_session->getRmaFormData(true);
        if (!empty($formData)) {
            $data = new \Magento\Framework\DataObject();
            $data->addData($formData);
            $this->setFormData($data);
        }
        $errorKeys = $this->_session->getRmaErrorKeys(true);
        if (!empty($errorKeys)) {
            $data = new \Magento\Framework\DataObject();
            $data->addData($errorKeys);
            $this->setErrorKeys($data);
        }
    }

    /**
     * Parent prepare layout override
     *
     * @return \Magento\Rma\Block\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     * @since 101.1.0
     */
    protected function _prepareLayout()
    {
        $result = parent::_prepareLayout();
        $result->setSubmitUrl($this->_rmaData->getReturnSubmitUrl(
            $this->_coreRegistry->registry('current_order')
        ));

        return $result;
    }

    /**
     * Retrieves item qty available for return
     *
     * @param  Item $item
     * @return int
     */
    public function getAvailableQty($item)
    {
        $return = $item->getAvailableQty();
        if (!$item->getIsQtyDecimal()) {
            $return = (int)$return;
        }
        return $return;
    }

    /**
     * Returns url for "back" button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->_urlBuilder->getUrl('sales/order/history');
    }

    /**
     * Prepare rma item attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        /* @var $itemModel \Magento\Rma\Model\Item */
        $itemModel = $this->_itemFactory->create();

        /* @var $itemForm \Magento\Rma\Model\Item\Form */
        $itemForm = $this->_itemFormFactory->create();
        $itemForm->setFormCode('default')->setStore($this->getStore())->setEntity($itemModel);

        // prepare item attributes to show
        $attributes = [];

        // add system required attributes
        foreach ($itemForm->getSystemAttributes() as $attribute) {
            /* @var $attribute Attribute */
            if ($attribute->getIsVisible()) {
                $attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        // add user defined attributes
        foreach ($itemForm->getUserAttributes() as $attribute) {
            /* @var $attribute Attribute */
            if ($attribute->getIsVisible()) {
                $attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        uasort(
            $attributes,
            // @codingStandardsIgnoreStart
            /**
             * Compares sort order of attributes, returns -1, 0 or 1 if $a sort
             * order is less, equal or greater than $b sort order respectively.
             *
             * @param Attribute $a
             * @param Attribute $b
             *
             * @return int
             */
            // @codingStandardsIgnoreEnd
            function (Attribute $a, Attribute $b) {
                $diff = $a->getSortOrder() - $b->getSortOrder();
                return $diff ? ($diff > 0 ? 1 : -1) : 0;
            }
        );

        return $attributes;
    }

    /**
     * Retrieves Contact Email Address on error
     *
     * @return string
     */
    public function getContactEmail()
    {
        $data = $this->getFormData();
        $email = '';

        if ($data) {
            $email = $this->escapeHtml($data->getCustomerCustomEmail());
        }
        return $email;
    }

    /**
     * Format address by a specified renderer way
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @param string $format
     * @return null|string
     */
    public function format(\Magento\Sales\Model\Order\Address $address, $format)
    {
        return $this->addressRenderer->format($address, $format);
    }
}
