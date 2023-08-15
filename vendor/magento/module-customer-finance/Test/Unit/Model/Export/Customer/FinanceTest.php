<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerFinance\Test\Unit\Model\Export\Customer;

use Magento\CustomerFinance\Helper\Data;
use Magento\CustomerFinance\Model\Export\Customer\Finance;
use Magento\CustomerFinance\Model\ResourceModel\Customer\CollectionFactory;
use Magento\CustomerImportExport\Model\Export\CustomerFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;
use Magento\ImportExport\Model\Export\Factory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FinanceTest extends TestCase
{
    /**#@+
     * Test attribute code and website specific attribute code
     */
    const ATTRIBUTE_CODE = 'code1';

    const WEBSITE_ATTRIBUTE_CODE = 'website1_code1';

    /**#@-*/

    /**
     * Websites array (website id => code)
     *
     * @var array
     */
    protected $_websites = [Store::DEFAULT_STORE_ID => 'admin', 1 => 'website1'];

    /**
     * Attributes array
     *
     * @var array
     */
    protected $_attributes = [['attribute_id' => 1, 'attribute_code' => self::ATTRIBUTE_CODE]];

    /**
     * Customer data
     *
     * @var array
     */
    protected $_customerData = [
        'website_id' => 1,
        'email' => '@email@domain.com',
        self::WEBSITE_ATTRIBUTE_CODE => 1,
    ];

    /**
     * Customer financial data export model
     *
     * @var Finance
     */
    protected $_model;

    protected function setUp(): void
    {
        $scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $customerCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $eavCustomerFactory = $this->createPartialMock(
            CustomerFactory::class,
            ['create']
        );

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->expects(
            $this->exactly(2)
        )->method(
            'getWebsites'
        )->willReturnCallback(
            [$this, 'getWebsites']
        );

        $this->_model = new Finance(
            $scopeConfig,
            $storeManager,
            $this->createPartialMock(Factory::class, ['create']),
            $this->createPartialMock(
                CollectionByPagesIteratorFactory::class,
                ['create']
            ),
            $customerCollectionFactory,
            $eavCustomerFactory,
            $this->createMock(Data::class),
            $this->_getModelDependencies()
        );
    }

    protected function tearDown(): void
    {
        unset($this->_model);
    }

    /**
     * Create mocks for all $this->_model dependencies
     *
     * @return array
     */
    protected function _getModelDependencies()
    {
        $objectManagerHelper = new ObjectManager($this);

        $translator = $this->createMock(\stdClass::class);

        /** @var Collection|TestCase $attributeCollection */
        $attributeCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(['getEntityTypeCode'])
            ->setConstructorArgs([$this->createMock(EntityFactory::class)])
            ->getMock();

        foreach ($this->_attributes as $attributeData) {
            $arguments = $objectManagerHelper->getConstructArguments(
                AbstractAttribute::class
            );
            $arguments['data'] = $attributeData;
            $attribute = $this->getMockBuilder(
                AbstractAttribute::class
            )->setConstructorArgs(
                $arguments
            )->setMethods(
                ['_construct']
            )->getMock();
            $attributeCollection->addItem($attribute);
        }

        $data = [
            'translator' => $translator,
            'attribute_collection' => $attributeCollection,
            'page_size' => 1,
            'collection_by_pages_iterator' => 'not_used',
            'entity_type_id' => 1,
            'customer_collection' => 'not_used',
            'customer_entity' => 'not_used',
            'module_helper' => 'not_used',
        ];

        return $data;
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
     * Test for method exportItem()
     *
     * @covers \Magento\CustomerFinance\Model\Export\Customer\Finance::exportItem
     */
    public function testExportItem()
    {
        $writer = $this->getMockForAbstractClass(
            AbstractAdapter::class,
            [],
            '',
            false,
            false,
            true,
            ['writeRow']
        );

        $writer->expects(
            $this->once()
        )->method(
            'writeRow'
        )->willReturnCallback(
            [$this, 'validateWriteRow']
        );

        $this->_model->setWriter($writer);

        $item = $this->getMockForAbstractClass(
            AbstractModel::class,
            [],
            '',
            false,
            false,
            true,
            ['__wakeup']
        );
        /** @var AbstractModel $item */
        $item->setData($this->_customerData);

        $this->_model->exportItem($item);
    }

    /**
     * Validate data passed to writer's writeRow() method
     *
     * @param array $row
     */
    public function validateWriteRow(array $row)
    {
        $emailColumn = Finance::COLUMN_EMAIL;
        $this->assertEquals($this->_customerData['email'], $row[$emailColumn]);

        $websiteColumn = Finance::COLUMN_WEBSITE;
        $this->assertEquals($this->_websites[$this->_customerData['website_id']], $row[$websiteColumn]);

        $financeWebsiteCol = Finance::COLUMN_FINANCE_WEBSITE;
        $this->assertEquals($this->_websites[$this->_customerData['website_id']], $row[$financeWebsiteCol]);

        $this->assertEquals($this->_customerData[self::WEBSITE_ATTRIBUTE_CODE], $row[self::ATTRIBUTE_CODE]);
    }
}
