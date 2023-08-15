<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer account reward history block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Block\Customer\Reward;

/**
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Framework\View\Element\Template
{
    /**
     * History records collection
     *
     * @var \Magento\Reward\Model\ResourceModel\Reward\History\Collection
     */
    protected $_collection = null;

    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory
     */
    protected $_historyFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $historyFactory
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $historyFactory,
        array $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->_rewardData = $rewardData;
        $this->currentCustomer = $currentCustomer;
        $this->_historyFactory = $historyFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get history collection if needed
     *
     * @return \Magento\Reward\Model\ResourceModel\Reward\History\Collection|false
     */
    public function getHistory()
    {
        if (0 == $this->_getCollection()->getSize()) {
            return false;
        }
        return $this->_collection;
    }

    /**
     * History item points delta getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getPointsDelta(\Magento\Reward\Model\Reward\History $item)
    {
        return $this->_rewardData->formatPointsDelta($item->getPointsDelta());
    }

    /**
     * History item points balance getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getPointsBalance(\Magento\Reward\Model\Reward\History $item)
    {
        return $item->getPointsBalance();
    }

    /**
     * History item currency balance getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getCurrencyBalance(\Magento\Reward\Model\Reward\History $item)
    {
        return $this->pricingHelper->currency($item->getCurrencyAmount());
    }

    /**
     * History item reference message getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getMessage(\Magento\Reward\Model\Reward\History $item)
    {
        return $item->getMessage();
    }

    /**
     * History item reference additional explanation getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codeCoverageIgnore
     */
    public function getExplanation(\Magento\Reward\Model\Reward\History $item)
    {
        return ''; // TODO
    }

    /**
     * History item creation date getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getDate(\Magento\Reward\Model\Reward\History $item)
    {
        return $this->formatDate($item->getCreatedAt(), \IntlDateFormatter::SHORT, true);
    }

    /**
     * History item expiration date getter
     *
     * @param \Magento\Reward\Model\Reward\History $item
     * @return string
     */
    public function getExpirationDate(\Magento\Reward\Model\Reward\History $item)
    {
        $expiresAt = $item->getExpiresAt();
        if ($expiresAt) {
            return $this->formatDate($expiresAt, \IntlDateFormatter::SHORT, true);
        }
        return '';
    }

    /**
     * Return reword points update history collection by customer and website
     *
     * @return \Magento\Reward\Model\ResourceModel\Reward\History\Collection
     * @codeCoverageIgnore
     */
    protected function _getCollection()
    {
        if (!$this->_collection) {
            $websiteId = $this->_storeManager->getWebsite()->getId();
            $this->_collection = $this->_historyFactory->create()
                ->addCustomerFilter($this->currentCustomer->getCustomerId())
                ->addWebsiteFilter($websiteId)
                ->setExpiryConfig($this->_rewardData->getExpiryConfig())
                ->addExpirationDate($websiteId)
                ->setDefaultOrder();
        }
        return $this->_collection;
    }

    /**
     * Instantiate Pagination
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _prepareLayout()
    {
        if ($this->_isEnabled()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'reward.history.pager'
            )->setCollection(
                $this->_getCollection()
            )->setIsOutputRequired(
                false
            );
            $this->setChild('pager', $pager);
        }
        return parent::_prepareLayout();
    }

    /**
     * Whether the history may show up
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_isEnabled()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Whether the history is supposed to be rendered
     *
     * @return bool
     */
    protected function _isEnabled()
    {
        return $this->currentCustomer->getCustomerId() && $this->_rewardData->isEnabledOnFront()
            && $this->_rewardData->getGeneralConfig('publish_history');
    }
}
