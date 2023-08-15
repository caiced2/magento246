<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerFinance\Model\Import\Eav\Customer;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection as FinanceCollection;

/**
 * Import customer finance entity model
 *
 * @method array getData() getData()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Finance extends \Magento\CustomerImportExport\Model\Import\AbstractCustomer
{
    /**
     * Attribute collection name
     */
    const ATTRIBUTE_COLLECTION_NAME = FinanceCollection::class;

    /**#@+
     * Permanent column names
     *
     * Names that begins with underscore is not an attribute. This name convention is for
     * to avoid interference with same attribute name.
     */
    const COLUMN_EMAIL = '_email';

    const COLUMN_WEBSITE = '_website';

    const COLUMN_FINANCE_WEBSITE = '_finance_website';

    /**#@-*/

    /**#@+
     * Error codes
     */
    const ERROR_FINANCE_WEBSITE_IS_EMPTY = 'financeWebsiteIsEmpty';

    const ERROR_INVALID_FINANCE_WEBSITE = 'invalidFinanceWebsite';

    const ERROR_DUPLICATE_PK = 'duplicateEmailSiteFinanceSite';

    /**#@-*/

    /**#@-*/
    protected $_permanentAttributes = [self::COLUMN_WEBSITE, self::COLUMN_EMAIL, self::COLUMN_FINANCE_WEBSITE];

    /**
     * Column names that holds values with particular meaning
     *
     * @var string[]
     */
    protected $_specialAttributes = [
        self::COLUMN_ACTION,
        self::COLUMN_WEBSITE,
        self::COLUMN_EMAIL,
        self::COLUMN_FINANCE_WEBSITE,
    ];

    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = [
        FinanceCollection::COLUMN_CUSTOMER_BALANCE,
        FinanceCollection::COLUMN_REWARD_POINTS,
    ];

    /**
     * Comment for finance data import
     *
     * @var string
     */
    protected $_comment;

    /**
     * Address attributes collection
     *
     * @var \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection
     */
    protected $_attributeCollection;

    /**
     * Helper to check whether modules are enabled/disabled
     *
     * @var \Magento\CustomerFinance\Helper\Data
     */
    protected $_customerFinanceData;

    /**
     * Admin user object
     *
     * @var \Magento\User\Model\User
     */
    protected $_adminUser;

    /**
     * Store imported row primary keys
     *
     * @var array
     */
    protected $_importedRowPks = [];

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\CustomerFinance\Helper\Data $customerFinanceData
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\CustomerFinance\Helper\Data $customerFinanceData,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        array $data = []
    ) {
        // entity type id has no meaning for finance import
        $data['entity_type_id'] = -1;

        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $storeManager,
            $collectionFactory,
            $eavConfig,
            $storageFactory,
            $data
        );

        $this->_rewardFactory = $rewardFactory;
        $this->_customerFactory = $customerFactory;
        $this->_balanceFactory = $balanceFactory;
        $this->_customerFinanceData = $customerFinanceData;

        $this->_adminUser = isset($data['admin_user']) ? $data['admin_user'] : $authSession->getUser();

        $this->addMessageTemplate(
            self::ERROR_FINANCE_WEBSITE_IS_EMPTY,
            __('Please specify a finance information website.')
        );
        $this->addMessageTemplate(
            self::ERROR_INVALID_FINANCE_WEBSITE,
            __('Please specify a valid finance information website.')
        );
        $this->addMessageTemplate(
            self::ERROR_DUPLICATE_PK,
            __('A row with this email, website, and finance website combination already exists.')
        );
        $this->_initAttributes();
    }

    /**
     * Initialize entity attributes
     *
     * @return $this
     */
    protected function _initAttributes()
    {
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->_attributeCollection as $attribute) {
            $this->_attributes[$attribute->getAttributeCode()] = [
                'id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'is_required' => $attribute->getIsRequired(),
                'type' => $attribute->getBackendType(),
            ];
        }
        return $this;
    }

    /**
     * Import data rows
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _importData()
    {
        if (!$this->_customerFinanceData->isRewardPointsEnabled()
            && !$this->_customerFinanceData->isCustomerBalanceEnabled()
        ) {
            return false;
        }

        /** @var $customer \Magento\Customer\Model\Customer */
        $customer = $this->_customerFactory->create();
        $rewardPointsKey = FinanceCollection::COLUMN_REWARD_POINTS;
        $customerBalanceKey = FinanceCollection::COLUMN_CUSTOMER_BALANCE;

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {
                // check row data
                if (!$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }
                // load customer object
                $customerId = $this->_getCustomerId($rowData[self::COLUMN_EMAIL], $rowData[self::COLUMN_WEBSITE]);
                if ($customer->getId() != $customerId) {
                    $customer->reset();
                    $customer->load($customerId);
                }

                $websiteId = $this->_websiteCodeToId[$rowData[self::COLUMN_FINANCE_WEBSITE]];
                // save finance data for customer
                foreach ($this->_attributes as $attributeCode => $attributeParams) {
                    if ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                        if ($attributeCode == $rewardPointsKey) {
                            $this->_deleteRewardPoints($customer, $websiteId);
                        } elseif ($attributeCode == $customerBalanceKey) {
                            $this->_deleteCustomerBalance($customer, $websiteId);
                        }
                    } elseif ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE
                    ) {
                        if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                            if ($attributeCode == $rewardPointsKey) {
                                $this->_updateRewardPointsForCustomer($customer, $websiteId, $rowData[$attributeCode]);
                            } elseif ($attributeCode == $customerBalanceKey) {
                                $this->_updateCustomerBalanceForCustomer(
                                    $customer,
                                    $websiteId,
                                    $rowData[$attributeCode]
                                );
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update reward points value for customerEtn
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $websiteId
     * @param int $value reward points value
     * @return \Magento\Reward\Model\Reward
     */
    protected function _updateRewardPointsForCustomer(\Magento\Customer\Model\Customer $customer, $websiteId, $value)
    {
        /** @var $rewardModel \Magento\Reward\Model\Reward */
        $rewardModel = $this->_rewardFactory->create();
        $rewardModel->setCustomer($customer)->setWebsiteId($websiteId)->loadByCustomer();

        return $this->_updateRewardValue($rewardModel, $value);
    }

    /**
     * Update reward points value for reward model
     *
     * @param \Magento\Reward\Model\Reward $rewardModel
     * @param int $value reward points value
     * @return \Magento\Reward\Model\Reward
     */
    protected function _updateRewardValue(\Magento\Reward\Model\Reward $rewardModel, $value)
    {
        $pointsDelta = $value - $rewardModel->getPointsBalance();
        if ($pointsDelta != 0) {
            $rewardModel->setPointsDelta(
                $pointsDelta
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_ADMIN
            )->setComment(
                $this->_getComment()
            )->updateRewardPoints();
        }

        return $rewardModel;
    }

    /**
     * Update store credit balance for customer
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $websiteId
     * @param float $value store credit balance
     * @return \Magento\CustomerBalance\Model\Balance
     */
    protected function _updateCustomerBalanceForCustomer(
        \Magento\Customer\Model\Customer $customer,
        $websiteId,
        $value
    ) {
        /** @var $balanceModel \Magento\CustomerBalance\Model\Balance */
        $balanceModel = $this->_balanceFactory->create();
        $balanceModel->setCustomer($customer)->setWebsiteId($websiteId)->loadByCustomer();

        return $this->_updateCustomerBalanceValue($balanceModel, $value);
    }

    /**
     * Update balance for customer balance model
     *
     * @param \Magento\CustomerBalance\Model\Balance $balanceModel
     * @param float $value store credit balance
     * @return \Magento\CustomerBalance\Model\Balance
     */
    protected function _updateCustomerBalanceValue(\Magento\CustomerBalance\Model\Balance $balanceModel, $value)
    {
        $amountDelta = $value - $balanceModel->getAmount();
        if ($amountDelta != 0) {
            $balanceModel->setAmountDelta($amountDelta)->setComment($this->_getComment())->save();
        }

        return $balanceModel;
    }

    /**
     * Delete reward points value for customer (just set it to 0)
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $websiteId
     * @return void
     */
    protected function _deleteRewardPoints(\Magento\Customer\Model\Customer $customer, $websiteId)
    {
        $this->_updateRewardPointsForCustomer($customer, $websiteId, 0);
    }

    /**
     * Delete store credit balance for customer (just set it to 0)
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $websiteId
     * @return void
     */
    protected function _deleteCustomerBalance(\Magento\Customer\Model\Customer $customer, $websiteId)
    {
        $this->_updateCustomerBalanceForCustomer($customer, $websiteId, 0);
    }

    /**
     * Retrieve comment string
     *
     * @return string
     */
    protected function _getComment()
    {
        if (!$this->_comment) {
            $this->_comment = __('Data was imported by %1', $this->_adminUser->getUsername());
        }

        return $this->_comment;
    }

    /**
     * Imported entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'customer_finance';
    }

    /**
     * Validate data row for add/update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_FINANCE_WEBSITE])) {
                $this->addRowError(self::ERROR_FINANCE_WEBSITE_IS_EMPTY, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
            } else {
                $email = strtolower($rowData[self::COLUMN_EMAIL]);
                $website = $rowData[self::COLUMN_WEBSITE];
                $financeWebsite = $rowData[self::COLUMN_FINANCE_WEBSITE];
                $customerId = $this->_getCustomerId($email, $website);

                $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
                if (!isset(
                    $this->_websiteCodeToId[$financeWebsite]
                ) || $this->_websiteCodeToId[$financeWebsite] == $defaultStoreId
                ) {
                    $this->addRowError(self::ERROR_INVALID_FINANCE_WEBSITE, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
                } elseif ($customerId === false) {
                    $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
                } elseif ($this->_checkRowDuplicate($customerId, $financeWebsite)) {
                    $this->addRowError(self::ERROR_DUPLICATE_PK, $rowNumber);
                } else {
                    // check simple attributes
                    foreach ($this->_attributes as $attributeCode => $attributeParams) {
                        if (in_array($attributeCode, $this->_ignoredAttributes)) {
                            continue;
                        }
                        if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                            $this->isAttributeValid($attributeCode, $attributeParams, $rowData, $rowNumber);
                        } elseif ($attributeParams['is_required']) {
                            $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNumber, $attributeCode);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate data row for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_FINANCE_WEBSITE])) {
                $this->addRowError(self::ERROR_FINANCE_WEBSITE_IS_EMPTY, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
            } else {
                $email = strtolower($rowData[self::COLUMN_EMAIL]);
                $website = $rowData[self::COLUMN_WEBSITE];
                $financeWebsite = $rowData[self::COLUMN_FINANCE_WEBSITE];

                $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
                if (!isset(
                    $this->_websiteCodeToId[$financeWebsite]
                ) || $this->_websiteCodeToId[$financeWebsite] == $defaultStoreId
                ) {
                    $this->addRowError(self::ERROR_INVALID_FINANCE_WEBSITE, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
                } elseif (!$this->_getCustomerId($email, $website)) {
                    $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
                }
            }
        }
    }

    /**
     * Check whether row with such email, website, finance website combination was already found in import file
     *
     * @param int $customerId
     * @param string $financeWebsite
     * @return bool
     */
    protected function _checkRowDuplicate($customerId, $financeWebsite)
    {
        $financeWebsiteId = $this->_websiteCodeToId[$financeWebsite];
        if (!isset($this->_importedRowPks[$customerId][$financeWebsiteId])) {
            $this->_importedRowPks[$customerId][$financeWebsiteId] = true;
            return false;
        } else {
            return true;
        }
    }
}
