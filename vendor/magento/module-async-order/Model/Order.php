<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model;

use Magento\Directory\Model\Currency;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface as OrderInterfaceDataApi;
use Magento\Sales\Model\EntityInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Initial async order model.
 */
class Order extends AbstractModel implements EntityInterface, OrderInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'async_sales_order';

    /**
     * @var string
     */
    protected $_eventObject = 'async_order';

    /**
     * Identifier for history item
     *
     * @var string
     */
    private $entityType = 'order';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        array $data = [],
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\AsyncOrder\Model\ResourceModel\Order::class);
    }

    /**
     * Retrieve store model instance.
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore(): StoreInterface
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->storeManager->getStore($storeId);
        }
        return $this->storeManager->getStore();
    }

    /**
     * Return order entity type.
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Return increment id.
     *
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getData('increment_id');
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId($id)
    {
        return $this->setData(OrderInterfaceDataApi::INCREMENT_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(OrderInterfaceDataApi::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($id)
    {
        return $this->setData(OrderInterfaceDataApi::STORE_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(OrderInterfaceDataApi::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(OrderInterfaceDataApi::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($id)
    {
        return $this->setData(OrderInterfaceDataApi::CUSTOMER_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(OrderInterfaceDataApi::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setGrandTotal($amount)
    {
        return $this->setData(OrderInterfaceDataApi::GRAND_TOTAL, $amount);
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal()
    {
        return $this->getData(OrderInterfaceDataApi::GRAND_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerEmail($email)
    {
        return $this->setData(OrderInterfaceDataApi::CUSTOMER_EMAIL, $email);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerEmail()
    {
        return $this->getData(OrderInterfaceDataApi::CUSTOMER_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setQuoteId($id)
    {
        return $this->setData(OrderInterfaceDataApi::QUOTE_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getQuoteId()
    {
        return $this->getData(OrderInterfaceDataApi::QUOTE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTotalItemCount($totalItemCount)
    {
        return $this->setData(OrderInterfaceDataApi::TOTAL_ITEM_COUNT, $totalItemCount);
    }

    /**
     * @inheritdoc
     */
    public function getTotalItemCount()
    {
        return $this->getData(OrderInterfaceDataApi::TOTAL_ITEM_COUNT);
    }

    /**
     * Return protect_code
     *
     * @return string|null
     */
    public function getProtectCode()
    {
        return $this->getData(OrderInterfaceDataApi::PROTECT_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setProtectCode($code)
    {
        return $this->setData(OrderInterfaceDataApi::PROTECT_CODE, $code);
    }

    /**
     * @inheritdoc
     */
    public function setBaseCurrencyCode($code)
    {
        return $this->setData(OrderInterfaceDataApi::BASE_CURRENCY_CODE, $code);
    }

    /**
     * Return base_currency_code
     *
     * @return string|null
     */
    public function getBaseCurrencyCode()
    {
        return $this->getData(OrderInterfaceDataApi::BASE_CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setGlobalCurrencyCode($code)
    {
        return $this->setData(OrderInterfaceDataApi::GLOBAL_CURRENCY_CODE, $code);
    }

    /**
     * Return global_currency_code
     *
     * @return string|null
     */
    public function getGlobalCurrencyCode()
    {
        return $this->getData(OrderInterfaceDataApi::GLOBAL_CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setOrderCurrencyCode($code)
    {
        return $this->setData(OrderInterfaceDataApi::ORDER_CURRENCY_CODE, $code);
    }

    /**
     * Return order_currency_code
     *
     * @return string|null
     */
    public function getOrderCurrencyCode()
    {
        return $this->getData(OrderInterfaceDataApi::ORDER_CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setStoreCurrencyCode($code)
    {
        return $this->setData(OrderInterfaceDataApi::STORE_CURRENCY_CODE, $code);
    }

    /**
     * Return store_currency_code
     *
     * @return string|null
     */
    public function getStoreCurrencyCode()
    {
        return $this->getData(OrderInterfaceDataApi::STORE_CURRENCY_CODE);
    }
}
