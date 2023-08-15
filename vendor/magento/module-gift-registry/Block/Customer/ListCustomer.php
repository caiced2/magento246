<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Customer giftregistry list block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class ListCustomer extends \Magento\Customer\Block\Account\Dashboard
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\GiftRegistry\Model\EntityFactory
     */
    protected $entityFactory;

    /**
     * @var \Magento\GiftRegistry\Model\TypeFactory
     */
    protected $typeFactory;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\GiftRegistry\Model\Entity
     */
    protected $collection;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $postDataHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\GiftRegistry\Model\EntityFactory $entityFactory
     * @param \Magento\GiftRegistry\Model\TypeFactory $typeFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        \Magento\GiftRegistry\Model\EntityFactory $entityFactory,
        \Magento\GiftRegistry\Model\TypeFactory $typeFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->entityFactory = $entityFactory;
        $this->typeFactory = $typeFactory;
        parent::__construct(
            $context,
            $customerSession,
            $subscriberFactory,
            $customerRepository,
            $customerAccountManagement,
            $data
        );
        $this->postDataHelper = $postDataHelper;
        $this->currentCustomer = $currentCustomer;
    }

    /**
     * Truncate string
     *
     * @param string $value
     * @param int $length
     * @param string $etc
     * @param string $remainder
     * @param bool $breakWords
     * @return string
     * @codeCoverageIgnore
     */
    public function truncateString($value, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        return $this->filterManager->truncate(
            $value,
            ['length' => $length, 'etc' => $etc, 'remainder' => $remainder, 'breakWords' => $breakWords]
        );
    }

    /**
     * Instantiate pagination
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if ($this->getEntityCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'giftregistry.list.pager'
            )->setCollection(
                $this->getEntityCollection()
            )->setIsOutputRequired(
                false
            );
            $this->setChild('pager', $pager);
        }
        return parent::_prepareLayout();
    }

    /**
     * Return list of gift registries
     *
     * @return false|\Magento\GiftRegistry\Model\ResourceModel\GiftRegistry\Collection
     */
    public function getEntityCollection()
    {
        if (!($customerId = $this->currentCustomer->getCustomerId())) {
            return false;
        }
        if (!$this->collection) {
            $this->collection = $this->entityFactory->create()->getCollection()->filterByCustomerId($customerId);
        }
        return $this->collection;
    }

    /**
     * Check exist listed gift registry types on the current store
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canAddNewEntity()
    {
        $collection = $this->typeFactory->create()
            ->getCollection()
            ->addStoreData($this->_storeManager->getStore()->getId())
            ->applyListedFilter();

        return (bool)$collection->getSize();
    }

    /**
     * Return add button form url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddUrl()
    {
        return $this->getUrl('magento_giftregistry/index/addselect');
    }

    /**
     * Return view entity items url
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemsUrl($item)
    {
        return $this->getUrl('magento_giftregistry/index/items', ['id' => $item->getEntityId()]);
    }

    /**
     * Return share entity url
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getShareUrl($item)
    {
        return $this->getUrl('magento_giftregistry/index/share', ['id' => $item->getEntityId()]);
    }

    /**
     * Return edit entity url
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getEditUrl($item)
    {
        return $this->getUrl('magento_giftregistry/index/edit', ['entity_id' => $item->getEntityId()]);
    }

    /**
     * Return delete post params
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getDeleteParams($item)
    {
        return $this->postDataHelper->getPostData(
            $this->getUrl('magento_giftregistry/index/delete'),
            ['id' => $item->getEntityId()]
        );
    }

    /**
     * Retrieve item title
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getEscapedTitle($item)
    {
        return $this->escapeHtml($item->getData('title'));
    }

    /**
     * Retrieve item formatted date
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormattedDate($item)
    {
        return $this->formatDate(
            new \DateTime($item->getCreatedAt()),
            \IntlDateFormatter::MEDIUM
        );
    }

    /**
     * Retrieve escaped item message
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getEscapedMessage($item)
    {
        return $this->escapeHtml($item->getData('message'));
    }

    /**
     * Retrieve item message
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getIsActive($item)
    {
        return $item->getData('is_active');
    }
}
