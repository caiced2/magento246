<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

/**
 * RMA Item model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Item extends \Magento\Sales\Model\AbstractModel implements \Magento\Rma\Api\Data\ItemInterface
{
    /**#@+
     * Constants defined for keys of array
     */
    const ENTITY_ID = 'entity_id';

    const RMA_ENTITY_ID = 'rma_entity_id';

    const ORDER_ITEM_ID = 'order_item_id';

    const QTY_REQUESTED = 'qty_requested';

    const QTY_AUTHORIZED = 'qty_authorized';

    const QTY_RETURNED = 'qty_returned';

    const QTY_APPROVED = 'qty_approved';

    const REASON = 'reason';

    const CONDITION = 'condition';

    const RESOLUTION = 'resolution';

    const STATUS = 'status';

    /**#@-*/

    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY = 'rma_item';

    /**
     * Rma instance
     *
     * @var \Magento\Rma\Model\Rma
     */
    protected $_rma = null;

    /**
     * Store firstly set all item attributes
     *
     * @var array
     */
    protected $_attributes;

    /**
     * Rma item $_FILES collection
     *
     * @var array
     */
    protected $_filesArray = [];

    /**
     * Rma item errors
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Image url
     */
    const ITEM_IMAGE_URL = 'rma_item';

    /**
     * Rma factory
     *
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;

    /**
     * Rma item attribute status factory
     *
     * @var \Magento\Rma\Model\Item\Attribute\Source\StatusFactory
     */
    protected $_statusFactory;

    /**
     * Sales order item factory
     *
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Rma item form factory
     *
     * @var \Magento\Rma\Model\Item\FormFactory
     */
    protected $_formFactory;

    /**
     * Application request factory
     *
     * @var \Magento\Framework\App\RequestFactory
     */
    protected $_requestFactory;

    /**
     * Serializer instance.
     *
     * @var Json
     */
    private $serializer;

    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'rma_item';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'rma_item';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param RmaFactory $rmaFactory
     * @param Item\Attribute\Source\StatusFactory $statusFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $itemFactory
     * @param Item\FormFactory $formFactory
     * @param \Magento\Framework\App\RequestFactory $requestFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $statusFactory,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Rma\Model\Item\FormFactory $formFactory,
        \Magento\Framework\App\RequestFactory $requestFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        $this->_rmaFactory = $rmaFactory;
        $this->_statusFactory = $statusFactory;
        $this->_itemFactory = $itemFactory;
        $this->_formFactory = $formFactory;
        $this->_requestFactory = $requestFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritdoc
     *
     * @return \Magento\Rma\Api\Data\ItemExtensionInterface
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param \Magento\Rma\Api\Data\ItemExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\ItemExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
    
    //@codeCoverageIgnoreStart

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getRmaEntityId()
    {
        return $this->getData(self::RMA_ENTITY_ID);
    }

    /**
     * Set RMA id
     *
     * @param int $id
     * @return $this
     */
    public function setRmaEntityId($id)
    {
        return $this->setData(self::RMA_ENTITY_ID, $id);
    }

    /**
     * Get order_item_id
     *
     * @return int
     */
    public function getOrderItemId()
    {
        return $this->getData(self::ORDER_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderItemId($id)
    {
        return $this->setData(self::ORDER_ITEM_ID, $id);
    }

    /**
     * Get qty_requested
     *
     * @return int
     */
    public function getQtyRequested()
    {
        return $this->getData(self::QTY_REQUESTED);
    }

    /**
     * @inheritdoc
     */
    public function setQtyRequested($qtyRequested)
    {
        return $this->setData(self::QTY_REQUESTED, $qtyRequested);
    }

    /**
     * Get qty_authorized
     *
     * @return int
     */
    public function getQtyAuthorized()
    {
        return $this->getData(self::QTY_AUTHORIZED);
    }

    /**
     * @inheritdoc
     */
    public function setQtyAuthorized($qtyAuthorized)
    {
        return $this->setData(self::QTY_AUTHORIZED, $qtyAuthorized);
    }

    /**
     * Get qty_approved
     *
     * @return int
     */
    public function getQtyApproved()
    {
        return $this->getData(self::QTY_APPROVED);
    }

    /**
     * @inheritdoc
     */
    public function setQtyApproved($qtyApproved)
    {
        return $this->setData(self::QTY_APPROVED, $qtyApproved);
    }

    /**
     * Get qty_returned
     *
     * @return int
     */
    public function getQtyReturned()
    {
        return $this->getData(self::QTY_RETURNED);
    }

    /**
     * @inheritdoc
     */
    public function setQtyReturned($qtyReturned)
    {
        return $this->setData(self::QTY_RETURNED, $qtyReturned);
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->getData(self::REASON);
    }

    /**
     * @inheritdoc
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->getData(self::CONDITION);
    }

    /**
     * @inheritdoc
     */
    public function setCondition($condition)
    {
        return $this->setData(self::CONDITION, $condition);
    }

    /**
     * Get resolution
     *
     * @return string
     */
    public function getResolution()
    {
        return $this->getData(self::RESOLUTION);
    }

    /**
     * @inheritdoc
     */
    public function setResolution($resolution)
    {
        return $this->setData(self::RESOLUTION, $resolution);
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    //@codeCoverageIgnoreEnd

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Rma\Model\ResourceModel\Item::class);
    }

    /**
     * Declare rma instance
     *
     * @param   Rma $rma
     * @return  $this
     */
    public function setRma(Rma $rma)
    {
        $this->_rma = $rma;
        $this->setRmaEntityId($rma->getId());
        return $this;
    }

    /**
     * Retrieve rma instance
     *
     * @return Rma
     */
    public function getRma()
    {
        $rmaId = $this->getRmaEntityId();
        if ($this->_rma === null && $rmaId) {
            /** @var $rma \Magento\Rma\Model\Rma */
            $rma = $this->_rmaFactory->create();
            $rma->load($rmaId);
            $this->setRma($rma);
        }
        return $this->_rma;
    }

    /**
     * Get RMA item's status label
     *
     * @return mixed
     */
    public function getStatusLabel()
    {
        if (parent::getStatusLabel() === null) {
            $this->setStatusLabel($this->_statusFactory->create()->getItemLabel($this->getStatus()));
        }
        return parent::getStatusLabel();
    }

    /**
     * Prepare data before save
     *
     * @return $this|void
     */
    public function beforeSave()
    {
        if (!$this->getRmaEntityId() && $this->getRma()) {
            $this->setRmaEntityId($this->getRma()->getId());
        }
        if ($this->getQtyAuthorized() === '') {
            $this->unsQtyAuthorized();
        }
        if ($this->getQtyReturned() === '') {
            $this->unsQtyReturned();
        }
        if ($this->getQtyApproved() === '') {
            $this->unsQtyApproved();
        }
        parent::beforeSave();
    }

    /**
     * Prepare data before save
     *
     * @return $this|void
     */
    public function afterSave()
    {
        $qtyReturnedChange = 0;
        if ($this->getOrigData('status') == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED) {
            if ($this->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED) {
                $qtyReturnedChange = $this->getQtyApproved() - $this->getOrigData('qty_approved');
            } else {
                $qtyReturnedChange = -$this->getOrigData('qty_approved');
            }
        } else {
            if ($this->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED) {
                $qtyReturnedChange = $this->getQtyApproved();
            }
        }

        if ($qtyReturnedChange) {
            $item = $this->_itemFactory->create()->load($this->getOrderItemId());
            if ($item->getId()) {
                $item->setQtyReturned($item->getQtyReturned() + $qtyReturnedChange)->save();
            }
        }
        parent::afterSave();
    }

    /**
     * Retrieve all item attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = $this->_getResource()->loadAllAttributes($this)->getSortedAttributes();
        }
        return $this->_attributes;
    }

    /**
     * Retrieve all item errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get rma item attribute model object
     *
     * @param   string $attributeCode
     * @return  \Magento\Rma\Model\Item\Attribute | null
     */
    public function getAttribute($attributeCode)
    {
        $this->getAttributes();
        if (isset($this->_attributes[$attributeCode])) {
            return $this->_attributes[$attributeCode];
        }
        return null;
    }

    /**
     * Prepares and adds $_POST data to item's attribute
     *
     * @param  array $itemPost
     * @param  int $key
     * @return string[]|null
     */
    public function prepareAttributes($itemPost, $key)
    {
        $httpRequest = $this->_requestFactory->create();
        $httpRequest->setPostValue($itemPost);

        /** @var $itemForm \Magento\Rma\Model\Item\Form */
        $itemForm = $this->_formFactory->create();
        $itemForm->setFormCode('default')->setEntity($this);
        $itemData = $itemForm->extractData($httpRequest);

        $files = [];
        //@codingStandardsIgnoreStart
        foreach ($itemData as $code => &$value) {
            if (is_array($value) && empty($value)) {
                if (array_key_exists($code . '_' . $key, $_FILES)) {
                    $value = $_FILES[$code . '_' . $key];
                    $files[] = $code;
                }
            }
        }

        $itemErrors = $itemForm->validateData($itemData);
        if ($itemErrors !== true) {
            $this->_errors = array_merge($itemErrors, $this->_errors);
        } else {
            $itemForm->compactData($itemData);
        }

        if (!empty($files)) {
            foreach ($files as $code) {
                unset($_FILES[$code . '_' . $key]);
            }
            return $files;
        }
        //@codingStandardsIgnoreEnd
    }

    /**
     * Gets item options
     *
     * @return array|false
     */
    public function getOptions()
    {
        $result = [];
        if ($this->getProductOptions()) {
            $options = $this->serializer->unserialize($this->getProductOptions());
            if ($options) {
                if (isset($options['options'])) {
                    $result = array_merge($result, $options['options']);
                }
                if (isset($options['additional_options'])) {
                    $result = array_merge($result, $options['additional_options']);
                }
                if (isset($options['attributes_info'])) {
                    $result = array_merge($result, $options['attributes_info']);
                }

                return $result;
            }
        }
        return false;
    }

    /**
     * Returns remaining qty of shipped items
     *
     * @param int $orderId
     * @param int $orderItemId
     * @return float|int
     */
    public function getReturnableQty($orderId = null, $orderItemId = null)
    {
        if (!$orderId) {
            $orderId = $this->getRma()->getOrderId();
        }
        if (!$orderItemId) {
            $orderItemId = $this->getOrderItemId();
        }
        $returnableItems = $this->getResource()->getReturnableItems($orderId);
        return isset($returnableItems[$orderItemId]) ? $returnableItems[$orderItemId] : 0;
    }
}
