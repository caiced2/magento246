<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management\Balance;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Reward points balance grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Flag to store if customer has orphan points
     *
     * @var bool
     */
    protected $_customerHasOrphanPoints = false;

    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Reward collection
     *
     * @var \Magento\Reward\Model\ResourceModel\Reward\CollectionFactory
     */
    protected $_rewardsFactory;

    /**
     * Reward website factory
     *
     * @var \Magento\Reward\Model\Source\WebsiteFactory
     */
    protected $_websitesFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Reward\Model\ResourceModel\Reward\CollectionFactory $rewardsFactory
     * @param \Magento\Reward\Model\Source\WebsiteFactory $websitesFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Reward\Model\ResourceModel\Reward\CollectionFactory $rewardsFactory,
        \Magento\Reward\Model\Source\WebsiteFactory $websitesFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_rewardData = $rewardData;
        $this->_rewardsFactory = $rewardsFactory;
        $this->_websitesFactory = $websitesFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Internal constructor
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('rewardPointsBalanceGrid');
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
    }

    /**
     * Getter
     *
     * @return \Magento\Customer\Model\Customer
     * @codeCoverageIgnore
     */
    public function getCustomer()
    {
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Prepare grid collection
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _prepareCollection()
    {
        $collection = $this->_rewardsFactory->create()->addFieldToFilter('customer_id', $this->getCustomer()->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * After load collection processing
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        parent::_afterLoadCollection();
        /* @var $item \Magento\Reward\Model\Reward */
        foreach ($this->getCollection() as $item) {
            $website = $item->getData('website_id');
            if ($website !== null) {
                $minBalance = $this->_rewardData->getGeneralConfig('min_points_balance', (int)$website);
                $maxBalance = $this->_rewardData->getGeneralConfig('max_points_balance', (int)$website);
                $item->addData(
                    [
                        'min_points_balance' => (int)$minBalance,
                        'max_points_balance' => !(int)$maxBalance ? __('Unlimited') : $maxBalance,
                    ]
                );
            } else {
                $this->_customerHasOrphanPoints = true;
                $item->addData(['min_points_balance' => __('No Data'), 'max_points_balance' => __('No Data')]);
            }
            $item->setCustomer($this->getCustomer());
        }
        return $this;
    }

    /**
     * Prepare grid columns
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _prepareColumns()
    {
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'website_id',
                [
                    'header' => __('Website'),
                    'index' => 'website_id',
                    'sortable' => false,
                    'type' => 'options',
                    'options' => $this->_websitesFactory->create()->toOptionArray(false)
                ]
            );
        }

        $this->addColumn(
            'points_balance',
            ['header' => __('Balance'), 'index' => 'points_balance', 'sortable' => false, 'align' => 'center']
        );

        $this->addColumn(
            'currency_amount',
            [
                'header' => __('Currency Amount'),
                'getter' => 'getFormatedCurrencyAmount',
                'align' => 'right',
                'sortable' => false
            ]
        );

        $this->addColumn(
            'min_balance',
            [
                'header' => __('Reward Points Threshold'),
                'index' => 'min_points_balance',
                'sortable' => false,
                'align' => 'center'
            ]
        );

        $this->addColumn(
            'max_balance',
            [
                'header' => __('Reward Points Cap'),
                'index' => 'max_points_balance',
                'sortable' => false,
                'align' => 'center'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Return url to delete orphan points
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDeleteOrphanPointsUrl()
    {
        return $this->getUrl('adminhtml/customer_reward/deleteOrphanPoints', ['_current' => true]);
    }

    /**
     * Processing block html after rendering.
     *
     * Add button to delete orphan points if customer has such points
     *
     * @param   string $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        $html = parent::_afterToHtml($html);
        if ($this->_customerHasOrphanPoints) {
            $confirmMessage = __('Are you sure you want to delete these points?');
            $deleteOrhanPointsButton = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class
            )->setData(
                [
                    'label' => __('Delete Orphan Points'),
                    'onclick' => 'deleteConfirm(\'' . $this->escapeJs($confirmMessage) . '\', \''
                        . $this->escapeUrl($this->getDeleteOrphanPointsUrl()) . '\', {data: {}})',
                    'class' => 'scalable delete',
                ]
            );
            $html .= $deleteOrhanPointsButton->toHtml();
        }
        return $html;
    }

    /**
     * Return grid row url
     *
     * @param \Magento\Reward\Model\Reward $row
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codeCoverageIgnore
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
