<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Model\Balance;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

/**
 * Customerbalance history model
 *
 * @method int getBalanceId()
 * @method \Magento\CustomerBalance\Model\Balance\History setBalanceId(int $value)
 * @method string getUpdatedAt()
 * @method \Magento\CustomerBalance\Model\Balance\History setUpdatedAt(string $value)
 * @method int getAction()
 * @method \Magento\CustomerBalance\Model\Balance\History setAction(int $value)
 * @method float getBalanceAmount()
 * @method \Magento\CustomerBalance\Model\Balance\History setBalanceAmount(float $value)
 * @method float getBalanceDelta()
 * @method \Magento\CustomerBalance\Model\Balance\History setBalanceDelta(float $value)
 * @method string getAdditionalInfo()
 * @method \Magento\CustomerBalance\Model\Balance\History setAdditionalInfo(string $value)
 * @method int getIsCustomerNotified()
 * @method \Magento\CustomerBalance\Model\Balance\History setIsCustomerNotified(int $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    const ACTION_UPDATED = 1;

    const ACTION_CREATED = 2;

    const ACTION_USED = 3;

    const ACTION_REFUNDED = 4;

    const ACTION_REVERTED = 5;

    /**
     * Design package instance
     *
     * @var \Magento\Framework\View\DesignInterface
     *
     * @deprecated 100.1.0
     */
    protected $_design = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerHelperView;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Customer\Helper\View $customerHelperView
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Helper\View $customerHelperView,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_design = $design;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->customerRegistry = $customerRegistry;
        $this->_customerHelperView = $customerHelperView;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\CustomerBalance\Model\ResourceModel\Balance\History::class);
    }

    /**
     * Available action names getter
     *
     * @return array
     */
    public function getActionNamesArray()
    {
        return [
            self::ACTION_CREATED => __('Created'),
            self::ACTION_UPDATED => __('Updated'),
            self::ACTION_USED => __('Used'),
            self::ACTION_REFUNDED => __('Refunded'),
            self::ACTION_REVERTED => __('Reverted')
        ];
    }

    /**
     * Validate balance history before saving
     *
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeSave()
    {
        $balance = $this->getBalanceModel();
        if (!$balance || !$balance->getId()) {
            throw new LocalizedException(__('A balance is needed to save a balance history.'));
        }

        $this->addData(
            [
                'balance_id' => $balance->getId(),
                'updated_at' => time(),
                'balance_amount' => $balance->getAmount(),
                'balance_delta' => $balance->getAmountDelta(),
            ]
        );

        switch ((int)$balance->getHistoryAction()) {
            case self::ACTION_CREATED:
                // break intentionally omitted
            case self::ACTION_UPDATED:
                if ($balance->getUpdatedActionAdditionalInfo()) {
                    $this->setAdditionalInfo($balance->getUpdatedActionAdditionalInfo());
                }
                break;
            case self::ACTION_USED:
                $this->_checkBalanceModelOrder($balance);
                $this->setAdditionalInfo(__('Order #%1', $balance->getOrder()->getIncrementId()));
                break;
            case self::ACTION_REFUNDED:
                $this->_checkBalanceModelOrder($balance);
                if (!$balance->getCreditMemo() || !$balance->getCreditMemo()->getIncrementId()) {
                    throw new LocalizedException(__('There is no credit memo set to balance model.'));
                }
                $this->setAdditionalInfo(
                    __(
                        'Order #%1, creditmemo #%2',
                        $balance->getOrder()->getIncrementId(),
                        $balance->getCreditMemo()->getIncrementId()
                    )
                );
                break;
            case self::ACTION_REVERTED:
                $this->_checkBalanceModelOrder($balance);
                $this->setAdditionalInfo(__('Order #%1', $balance->getOrder()->getIncrementId()));
                break;
            default:
                // break intentionally omitted
                throw new LocalizedException(
                    __('The balance history action code is unknown. Verify the code and try again.')
                );
        }
        $this->setAction((int)$balance->getHistoryAction());

        return parent::beforeSave();
    }

    /**
     * Send balance update if required
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        // attempt to send email
        $this->setIsCustomerNotified(false);
        if ($this->getBalanceModel()->getNotifyByEmail()) {
            $storeId = $this->getBalanceModel()->getStoreId();
            $customerModel = $this->getBalanceModel()->getCustomer();
            $customerId = $customerModel->getId();
            /* @var $customer \Magento\Customer\Model\Customer */
            $customer = $this->customerRegistry->retrieve($customerId);

            $transport = $this->_transportBuilder->setTemplateIdentifier(
                $this->_scopeConfig->getValue(
                    'customer/magento_customerbalance/email_template',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                )
            )->setTemplateOptions(
                ['area' => Area::AREA_FRONTEND, 'store' => $storeId]
            )->setTemplateVars(
                [
                    'balance' => $this->_storeManager->getWebsite(
                        $this->getBalanceModel()->getWebsiteId()
                    )->getBaseCurrency()->format(
                        $this->getBalanceModel()->getAmount(),
                        [],
                        false
                    ),
                    'name' => $this->_customerHelperView->getCustomerName($customerModel),
                    'store' => $this->_storeManager->getStore($storeId)
                ]
            )->setFromByScope(
                $this->_scopeConfig->getValue(
                    'customer/magento_customerbalance/email_identity',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                $storeId
            )->addTo(
                $customer->getEmail(),
                $this->_customerHelperView->getCustomerName($customerModel)
            )->getTransport();

            $transport->sendMessage();
            $this->getResource()->markAsSent($this->getId());
            $this->setIsCustomerNotified(true);
        }

        return $this;
    }

    /**
     * Validate order model for balance update
     *
     * @param \Magento\Sales\Model\Order $model
     * @return void
     * @throws LocalizedException
     */
    protected function _checkBalanceModelOrder($model)
    {
        if (!$model->getOrder()) {
            throw new LocalizedException(__('There is no order set to balance model.'));
        }
    }

    /**
     * Retrieve history data items as array
     *
     * @param string $customerId
     * @param string|null $websiteId
     * @return array
     */
    public function getHistoryData($customerId, $websiteId = null)
    {
        $result = [];
        /** @var $collection \Magento\CustomerBalance\Model\ResourceModel\Balance\History\Collection */
        $collection = $this->getCollection()->loadHistoryData($customerId, $websiteId);
        foreach ($collection as $historyItem) {
            $result[] = $historyItem->getData();
        }
        return $result;
    }
}
