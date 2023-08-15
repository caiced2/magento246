<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerFinance\Test\Unit\Model\Import\Eav\Customer;

use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerFinance\Helper\Data;
use Magento\CustomerFinance\Model\Import\Eav\Customer\Finance;
use Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection;
use Magento\CustomerImportExport\Model\Import\Address;
use Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\Storage;
use Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Export\Factory;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Test\Unit\Model\Import\AbstractImportTestCase;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FinanceTest extends AbstractImportTestCase
{
    /**
     * Customer financial data export model
     *
     * @var \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance
     */
    protected $_model;

    /**
     * Bunch counter for getNextBunch() stub method
     *
     * @var int
     */
    protected $_bunchNumber;

    /**
     * @var ProcessingErrorAggregatorInterface
     */
    protected $errorAggregator;

    /**
     * Websites array (website id => code)
     *
     * @var array
     */
    protected $_websites = [
        Store::DEFAULT_STORE_ID => 'admin',
        1 => 'website1',
        2 => 'website2',
    ];

    /**
     * Customers array
     *
     * @var array
     */
    protected $_customers = [
        ['entity_id' => 1, 'email' => 'test1@email.com', 'website_id' => 1],
        ['entity_id' => 2, 'email' => 'test2@email.com', 'website_id' => 2],
    ];

    /**
     * Attributes array
     *
     * @var array
     */
    protected $_attributes = [
        [
            'id' => 1,
            'attribute_code' => Collection::COLUMN_CUSTOMER_BALANCE,
            'frontend_label' => 'Store Credit',
            'backend_type' => 'decimal',
            'is_required' => true,
        ],
        [
            'id' => 2,
            'attribute_code' => Collection::COLUMN_REWARD_POINTS,
            'frontend_label' => 'Reward Points',
            'backend_type' => 'int',
            'is_required' => false
        ],
    ];

    /**
     * Input data
     *
     * @var array
     */
    protected $_inputData = [
        [
            Finance::COLUMN_EMAIL => 'test1@email.com',
            Finance::COLUMN_WEBSITE => 'website1',
            Finance::COLUMN_FINANCE_WEBSITE => 'website1',
            AbstractEntity::COLUMN_ACTION => null,
            Address::COLUMN_ADDRESS_ID => 1,
            Collection::COLUMN_CUSTOMER_BALANCE => 100,
            Collection::COLUMN_REWARD_POINTS => 200,
        ],
        [
            Finance::COLUMN_EMAIL => 'test2@email.com',
            Finance::COLUMN_WEBSITE => 'website2',
            Finance::COLUMN_FINANCE_WEBSITE => 'website1',
            AbstractEntity::COLUMN_ACTION => AbstractEntity::COLUMN_ACTION_VALUE_DELETE,
            Address::COLUMN_ADDRESS_ID => 2
        ],
        [
            Finance::COLUMN_EMAIL => 'test2@email.com',
            Finance::COLUMN_WEBSITE => 'website2',
            Finance::COLUMN_FINANCE_WEBSITE => 'website1',
            AbstractEntity::COLUMN_ACTION => 'update',
            Address::COLUMN_ADDRESS_ID => 2,
            Collection::COLUMN_CUSTOMER_BALANCE => 100,
            Collection::COLUMN_REWARD_POINTS => 200
        ],
    ];

    /**
     * Init entity adapter model
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_bunchNumber = 0;
        if ($this->getName() == 'testImportDataCustomBehavior') {
            $dependencies = $this->_getModelDependencies(true);
        } else {
            $dependencies = $this->_getModelDependencies();
        }

        $moduleHelper = $this->getMockBuilder(Data::class)
            ->addMethods(['__'])
            ->onlyMethods(['isRewardPointsEnabled', 'isCustomerBalanceEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $moduleHelper->expects($this->any())
            ->method('__')
            ->willReturnArgument(0);
        $moduleHelper->expects($this->any())
            ->method('isRewardPointsEnabled')
            ->willReturn(true);
        $moduleHelper->expects($this->any())
            ->method('isCustomerBalanceEnabled')
            ->willReturn(true);

        $customerFactory = $this->createPartialMock(CustomerFactory::class, ['create']);
        $balanceFactory = $this->createPartialMock(BalanceFactory::class, ['create']);
        $rewardFactory = $this->createPartialMock(RewardFactory::class, ['create']);

        $customerFactory->expects(
            $this->any()
        )
            ->method(
                'create'
            )
            ->willReturn(
                $this->getModelInstance(Customer::class)
            );
        $balanceFactory->expects(
            $this->any()
        )
            ->method(
                'create'
            )
            ->willReturn(
                $this->getModelInstance(Balance::class)
            );
        $rewardFactory->expects(
            $this->any()
        )
            ->method(
                'create'
            )
            ->willReturn(
                $this->getModelInstance(Reward::class)
            );

        $scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);

        $adminUser = $this->getMockBuilder(\stdClass::class)->addMethods(['getUsername'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminUser->expects($this->any())
            ->method('getUsername')
            ->willReturn('admin');
        $authSession = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $authSession->expects($this->once())
            ->method('getUser')
            ->willReturn($adminUser);

        $storeManager = $this->createPartialMock(StoreManager::class, ['getWebsites']);
        $storeManager->expects(
            $this->once()
        )
            ->method(
                'getWebsites'
            )
            ->willReturnCallback(
                [$this, 'getWebsites']
            );

        $this->errorAggregator = $this->getErrorAggregatorObject();

        $this->_model = new Finance(
            new StringUtils(),
            $scopeConfig,
            $this->createPartialMock(ImportFactory::class, ['create']),
            $this->createMock(Helper::class),
            $this->createMock(ResourceConnection::class),
            $this->errorAggregator,
            $storeManager,
            $this->createPartialMock(Factory::class, ['create']),
            $this->createMock(Config::class),
            $this->createPartialMock(
                StorageFactory::class,
                ['create']
            ),
            $authSession,
            $moduleHelper,
            $customerFactory,
            $balanceFactory,
            $rewardFactory,
            $dependencies
        );
    }

    /**
     * Unset entity adapter model
     */
    protected function tearDown(): void
    {
        unset($this->_model);
        unset($this->_bunchNumber);
    }

    /**
     * Create mocks for all $this->_model dependencies
     *
     * @param bool $addData
     * @return array
     */
    protected function _getModelDependencies($addData = false)
    {
        $objectManagerHelper = new ObjectManager($this);

        $dataSourceModel = $this->getMockBuilder(\stdClass::class)->addMethods(['getNextBunch'])
            ->disableOriginalConstructor()
            ->getMock();
        if ($addData) {
            $dataSourceModel->expects(
                $this->exactly(2)
            )
                ->method(
                    'getNextBunch'
                )
                ->willReturnCallback(
                    [$this, 'getNextBunch']
                );
        }

        $connection = $this->createMock(\stdClass::class);

        /** @var Storage|\PHPUnit\Framework\MockObject\MockObject $customerStorage */
        $customerStorage = $this->getMockBuilder(Storage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerStorage->expects($this->any())
            ->method('getCustomerId')
            ->willReturnCallback(
                function ($email, $websiteId) {
                    foreach ($this->_customers as $customerData) {
                        if ($customerData['email'] === $email
                            && $customerData['website_id'] === $websiteId
                        ) {
                            return $customerData['entity_id'];
                        }
                    }

                    return false;
                }
            );

        $objectFactory = $this->getMockBuilder(\stdClass::class)->addMethods(['getModelInstance'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectFactory->expects(
            $this->any()
        )
            ->method(
                'getModelInstance'
            )
            ->willReturnCallback(
                [$this, 'getModelInstance']
            );

        /** @var \Magento\Framework\Data\Collection $attributeCollection */
        $attributeCollection = $this->getMockBuilder(\Magento\Framework\Data\Collection::class)
            ->setMethods(['getEntityTypeCode'])
            ->setConstructorArgs([$this->createMock(EntityFactory::class)])
            ->getMock();
        foreach ($this->_attributes as $attributeData) {
            /** @var AbstractAttribute $attribute */
            $arguments = $objectManagerHelper->getConstructArguments(
                AbstractAttribute::class,
                ['eavTypeFactory' => $this->createPartialMock(TypeFactory::class, ['create'])
                ]
            );
            $arguments['data'] = $attributeData;
            $attribute = $this->getMockForAbstractClass(
                AbstractAttribute::class,
                $arguments,
                '',
                true,
                true,
                true,
                ['_construct']
            );
            $attributeCollection->addItem($attribute);
        }

        $data = [
            'data_source_model' => $dataSourceModel,
            'connection' => $connection,
            'json_helper' => 'not_used',
            'page_size' => 1,
            'max_data_size' => 1,
            'bunch_size' => 1,
            'entity_type_id' => 1,
            'customer_storage' => $customerStorage,
            'object_factory' => $objectFactory,
            'attribute_collection' => $attributeCollection,
        ];

        return $data;
    }

    /**
     * Stub for next bunch of validated rows getter. It is callback function which is used to emulate work of data
     * source model. It should return data on first call and null on next call to emulate end of bunch.
     *
     * @return array|null
     */
    public function getNextBunch()
    {
        if ($this->_bunchNumber == 0) {
            $data = $this->_inputData;
        } else {
            $data = null;
        }
        $this->_bunchNumber++;

        return $data;
    }

    /**
     * Iterate stub
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @param int $pageSize
     * @param array $callbacks
     */
    public function iterate(\Magento\Framework\Data\Collection $collection, $pageSize, array $callbacks)
    {
        foreach ($collection as $customer) {
            foreach ($callbacks as $callback) {
                call_user_func($callback, $customer);
            }
        }
    }

    /**
     * Get websites stub
     *
     * @param bool $withDefault
     * @return array
     */
    public function getWebsites($withDefault = false)
    {
        $websites = [];
        foreach ($this->_websites as $id => $code) {
            if (!$withDefault && $id == Store::DEFAULT_STORE_ID) {
                continue;
            }
            $websiteData = ['id' => $id, 'code' => $code];
            $websites[$id] = new DataObject($websiteData);
        }
        if (!$withDefault) {
            unset($websites[0]);
        }

        return $websites;
    }

    /**
     * Callback method for mock object
     *
     * @param string $modelClass
     * @param array|object $constructArguments
     * @return MockObject
     */
    public function getModelInstance($modelClass = '', $constructArguments = [])
    {
        switch ($modelClass) {
            case Balance::class:
                $instance = $this->getMockBuilder($modelClass)
                    ->setMethods(
                        [
                            'setCustomer',
                            'setWebsiteId',
                            'loadByCustomer',
                            'getAmount',
                            'setAmountDelta',
                            'setComment',
                            'save',
                            '__wakeup'
                        ]
                    )
                    ->setConstructorArgs($constructArguments)
                    ->disableOriginalConstructor()
                    ->getMock();
                $instance->expects($this->any())
                    ->method('setCustomer')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('setWebsiteId')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('loadByCustomer')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('getAmount')
                    ->willReturn(0);
                $instance->expects($this->any())
                    ->method('setAmountDelta')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('setComment')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('save')->willReturnSelf();
                break;
            case Reward::class:
                $instance = $this->getMockBuilder($modelClass)
                    ->setMethods(
                        [
                            'setCustomer',
                            'setWebsiteId',
                            'loadByCustomer',
                            'getPointsBalance',
                            'setPointsDelta',
                            'setAction',
                            'setComment',
                            'updateRewardPoints',
                            '__wakeup'
                        ]
                    )
                    ->setConstructorArgs($constructArguments)
                    ->disableOriginalConstructor()
                    ->getMock();
                $instance->expects($this->any())
                    ->method('setCustomer')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('setWebsiteId')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('loadByCustomer')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('getPointsBalance')
                    ->willReturn(0);
                $instance->expects($this->any())
                    ->method('setPointsDelta')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('setAction')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('setComment')->willReturnSelf();
                $instance->expects($this->any())
                    ->method('updateRewardPoints')->willReturnSelf();
                break;
            default:
                $instance = $this->getMockBuilder($modelClass)
                    ->setConstructorArgs($constructArguments)
                    ->disableOriginalConstructor()
                    ->getMock();
                break;
        }
        return $instance;
    }

    /**
     * Data provider of row data and errors
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function validateRowDataProvider()
    {
        return [
            'valid' => [
                '$rowData' => include __DIR__ . '/_files/row_data_valid.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => true,
                    Import::BEHAVIOR_DELETE => true,
                ],
            ],
            'no website' => [
                '$rowData' => include __DIR__ . '/_files/row_data_no_website.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'empty website' => [
                '$rowData' => include __DIR__ . '/_files/row_data_empty_website.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'no email' => [
                '$rowData' => include __DIR__ . '/_files/row_data_no_email.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'empty email' => [
                '$rowData' => include __DIR__ . '/_files/row_data_empty_email.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'empty finance website' => [
                '$rowData' => include __DIR__ . '/_files/row_data_empty_finance_website.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'invalid email' => [
                '$rowData' => include __DIR__ . '/_files/row_data_invalid_email.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'invalid website' => [
                '$rowData' => include __DIR__ . '/_files/row_data_invalid_website.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'invalid finance website' => [
                '$rowData' => include __DIR__ . '/_files/row_data_invalid_finance_website.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'invalid finance website (admin)' => [
                '$rowData' => include __DIR__ . '/_files/row_data_invalid_finance_website_admin.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'no customer' => [
                '$rowData' => include __DIR__ . '/_files/row_data_no_customer.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => false,
                ],
            ],
            'invalid_attribute_value' => [
                '$rowData' => include __DIR__ . '/_files/row_data_invalid_attribute_value.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => true,
                ],
            ],
            'empty_optional_attribute_value' => [
                '$rowData' => include __DIR__ . '/_files/row_data_empty_optional_attribute_value.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => true,
                    Import::BEHAVIOR_DELETE => true,
                ],
            ],
            'empty_required_attribute_value' => [
                '$rowData' => include __DIR__ . '/_files/row_data_empty_required_attribute_value.php',
                '$behaviors' => [
                    Import::BEHAVIOR_ADD_UPDATE => false,
                    Import::BEHAVIOR_DELETE => true,
                ],
            ]
        ];
    }

    /**
     * Test Finance::validateRow()
     * with different values in case when add/update behavior is performed
     *
     * @covers \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance::_validateRowForUpdate
     * @dataProvider validateRowDataProvider
     *
     * @param array $rowData
     * @param array $behaviors
     */
    public function testValidateRowForUpdate(array $rowData, array $behaviors)
    {
        $behavior = Import::BEHAVIOR_ADD_UPDATE;

        $this->_model->setParameters(['behavior' => $behavior]);

        $this->assertEquals($behaviors[$behavior], $this->_model->validateRow($rowData, 0));
    }

    /**
     * Test Finance::validateRow()
     * with 2 rows with identical PKs in case when add/update behavior is performed
     *
     * @covers \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance::_validateRowForUpdate
     */
    public function testValidateRowForUpdateDuplicateRows()
    {
        $behavior = Import::BEHAVIOR_ADD_UPDATE;

        $this->_model->setParameters(['behavior' => $behavior]);

        $secondRow = $firstRow = [
            '_website' => 'website1',
            '_email' => 'test1@email.com',
            '_finance_website' => 'website2',
            'store_credit' => 10.5,
            'reward_points' => 5,
        ];
        $secondRow['store_credit'] = 20;
        $secondRow['reward_points'] = 30;

        $this->assertTrue($this->_model->validateRow($firstRow, 0));
        $this->assertFalse($this->_model->validateRow($secondRow, 1));
    }

    /**
     * Test Finance::validateRow()
     * with different values in case when delete behavior is performed
     *
     * @covers \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance::_validateRowForDelete
     * @dataProvider validateRowDataProvider
     *
     * @param array $rowData
     * @param array $behaviors
     */
    public function testValidateRowForDelete(array $rowData, array $behaviors)
    {
        $behavior = Import::BEHAVIOR_DELETE;

        $this->_model->setParameters(['behavior' => $behavior]);

        $this->assertEquals($behaviors[$behavior], $this->_model->validateRow($rowData, 0));
    }

    /**
     * Test entity type code getter
     *
     * @covers \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance::getEntityTypeCode
     */
    public function testGetEntityTypeCode()
    {
        $this->assertEquals('customer_finance', $this->_model->getEntityTypeCode());
    }

    /**
     * Test data import
     *
     * @covers \Magento\CustomerFinance\Model\Import\Eav\Customer\Finance::importData
     */
    public function testImportDataCustomBehavior()
    {
        $this->assertTrue($this->_model->importData());
    }
}
