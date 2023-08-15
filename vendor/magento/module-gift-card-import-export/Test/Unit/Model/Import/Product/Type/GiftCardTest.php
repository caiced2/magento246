<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardImportExport\Test\Unit\Model\Import\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\StoreResolver;
use Magento\Config\Model\Config\Source\Email\Template;
use Magento\Config\Model\Config\Source\Email\TemplateFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManager;
use Magento\GiftCardImportExport\Model\Import\Product\Type\GiftCard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCardTest extends TestCase
{
    /**
     * @var ObjectManager|GiftCard
     */
    protected $giftcardModel;

    /**
     * @var MockObject
     */
    protected $attrSetColFacMock;

    /**
     * @var Collection|MockObject
     */
    protected $attrSetColMock;

    /**
     * @var MockObject
     */
    protected $prodAttrColFacMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Select|MockObject
     */
    protected $select;

    /**
     * @var Product|MockObject
     */
    protected $entityModelMock;

    /**
     * @var StoreResolver|MockObject
     */
    protected $storeResolverMock;

    /**
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    /**
     * @var Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var TemplateFactory|MockObject
     */
    protected $templateFactory;

    /**
     * @var Template|MockObject
     */
    protected $template;

    /**
     * @var Phrase|MockObject
     */
    protected $phrase;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->objectManager = $objectManager;
        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->addMethods(['joinLeft'])
            ->onlyMethods(
                [
                    'select',
                    'fetchAll',
                    'fetchPairs',
                    'insertOnDuplicate',
                    'delete',
                    'quoteInto',
                    'fetchAssoc'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->select = $this->createMock(Select::class);
        $this->select->expects($this->any())->method('from')->willReturnSelf();
        $this->select->expects($this->any())->method('where')->willReturnSelf();
        $this->select->expects($this->any())->method('joinLeft')->willReturnSelf();
        $adapter = $this->createMock(Mysql::class);
        $adapter->expects($this->any())->method('quoteInto')->willReturn('query');
        $this->select->expects($this->any())->method('getAdapter')->willReturn($adapter);
        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->select);
        $this->connectionMock->expects($this->any())->method('fetchAll')->willReturn(
            [
                [
                    'attribute_set_name' => '123',
                    'attribute_id' => '123'
                ]
            ]
        );
        $this->connectionMock->expects($this->any())->method('insertOnDuplicate')->willReturnSelf();
        $this->connectionMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->connectionMock->expects($this->any())->method('quoteInto')->willReturn('');
        $this->attrSetColFacMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->attrSetColMock = $this->createPartialMock(
            Collection::class,
            ['setEntityTypeFilter']
        );
        $this->attrSetColMock
            ->expects($this->any())
            ->method('setEntityTypeFilter')
            ->willReturn([]);
        $this->prodAttrColFacMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory::class,
            ['create']
        );
        $attrCollection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection::class);
        $this->attributeMock = $this->createPartialMock(
            Attribute::class,
            ['getId', 'getIsVisible', 'getAttributeCode']
        );
        $this->attributeMock->expects($this->any())->method('getAttributeCode')->willReturn('giftcard_amounts');
        $this->attributeMock->expects($this->any())->method('getIsVisible')->willReturn(true);
        $attrCollection->expects($this->any())->method('addFieldToFilter')->willReturn([$this->attributeMock]);
        $this->prodAttrColFacMock->expects($this->any())->method('create')->willReturn($attrCollection);
        $this->resourceMock = $this->createPartialMock(
            ResourceConnection::class,
            ['getConnection', 'getTableName']
        );
        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn(
            $this->connectionMock
        );
        $this->resourceMock->expects($this->any())->method('getTableName')->willReturn(
            'tableName'
        );
        $this->entityModelMock = $this->createPartialMock(
            Product::class,
            [
                'addMessageTemplate',
                'getEntityTypeId',
                'getBehavior',
                'getNewSku',
                'getNextBunch',
                'isRowAllowedToImport',
                'getParameters',
                'addRowError',
                'getRowScope'
            ]
        );
        $this->entityModelMock->expects($this->any())->method('addMessageTemplate')->willReturnSelf();
        $this->entityModelMock->expects($this->any())->method('getEntityTypeId')->willReturn(5);
        $this->entityModelMock->expects($this->any())->method('getParameters')->willReturn([]);
        $this->entityModelMock->expects($this->any())->method('getRowScope')->willReturn(
            \Magento\CatalogImportExport\Model\Import\Product::SCOPE_DEFAULT
        );
        $this->storeResolverMock = $this->createMock(
            StoreResolver::class
        );
        $this->phrase = $this->createPartialMock(Phrase::class, ['render']);
        $this->phrase->expects($this->any())->method('render')->willReturn('Template name');
        $this->template = $this->getMockBuilder(TemplateFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPath', 'toOptionArray'])
            ->getMock();
        $this->template->expects($this->any())->method('setPath')->willReturnSelf();
        $this->template->expects($this->any())
            ->method('toOptionArray')
            ->willReturn(
                [
                    [
                        'value' => '1',
                        'label' => $this->phrase
                    ]
                ]
            );
        $this->templateFactory = $this->createPartialMock(
            TemplateFactory::class,
            ['create']
        );
        $this->templateFactory->expects($this->any())->method('create')->willReturn($this->template);
        $metadataMock = $this->createMock(EntityMetadata::class);
        $metadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_id');
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($metadataMock);
    }

    private function createGiftCardModel()
    {
        $this->giftcardModel = $this->objectManager->getObject(
            GiftCard::class,
            [
                'attrSetColFac' => $this->attrSetColFacMock,
                'prodAttrColFac' => $this->prodAttrColFacMock,
                'resource' => $this->resourceMock,
                'params' => [
                    $this->entityModelMock,
                    'giftcard'
                ],
                'storeResolver' => $this->storeResolverMock,
                'templateFactory' => $this->templateFactory,
                'metadataPool' => $this->metadataPoolMock
            ]
        );
    }

    /**
     * Test saveData()
     *
     * @param array $bunch
     * @param array $sku
     * @param bool $allowed
     *
     * @return void
     * @dataProvider saveDataProvider
     */
    public function testSaveData(array $bunch, array $sku, bool $allowed): void
    {
        $this->entityModelMock
            ->method('getNextBunch')
            ->willReturnOnConsecutiveCalls($bunch);
        $this->entityModelMock->expects($this->any())->method('getNewSku')->willReturn($sku);
        $this->entityModelMock->expects($this->any())->method('isRowAllowedToImport')->willReturn($allowed);
        $this->attributeMock->expects($this->any())->method('getId')->willReturn(123);
        $this->createGiftCardModel();
        $this->assertInstanceOf(
            GiftCard::class,
            $this->giftcardModel->saveData()
        );
    }

    /**
     * Test isRowValid()
     *
     * @param int|null $attributeId
     * @param string   $amount
     * @param bool     $result
     *
     * @return void
     * @dataProvider isValidDataProvider
     */
    public function testIsRowValid(?int $attributeId, string $amount, bool $result): void
    {
        $rowData = [
            'sku' => 'giftcardsku',
            'attribute_set_code' => 'Default',
            'product_type' => 'giftcard',
            'name' => 'giftcard',
            'giftcard_type' => 'virtual',
            'giftcard_amount' => $amount,
            'giftcard_allow_open_amount' => '',
            'giftcard_open_amount_min' => '',
            'giftcard_open_amount_max' => '',
            'giftcard_is_redeemable' => '0',
            'giftcard_lifetime' => '1',
            'giftcard_allow_message' => '1',
            'giftcard_email_template' => 'Default',
            '_attribute_set' => '123',
        ];
        $this->attributeMock->expects($this->any())->method('getId')->willReturn($attributeId);
        $this->createGiftCardModel();
        $this->assertEquals($result, $this->giftcardModel->isRowValid($rowData, 1));
    }

    /**
     * @param array $rowData
     *
     * @return void
     * @dataProvider prepareAttributesWithDefaultValueForSaveDataProvider
     */
    public function testPrepareAttributesWithDefaultValueForSave(array $rowData): void
    {
        $this->createGiftCardModel();
        $resultAttributes = $this->giftcardModel->prepareAttributesWithDefaultValueForSave($rowData);
        $this->assertNull($resultAttributes['weight']);
        $this->assertArrayNotHasKey('giftcard_allow_open_amount', $resultAttributes);
        $this->assertArrayHasKey('allow_open_amount', $resultAttributes);
    }

    /**
     * @return array
     */
    public function prepareAttributesWithDefaultValueForSaveDataProvider(): array
    {
        return [
            [
                'rowData' => [
                    'sku' => 'giftcardsku',
                    'attribute_set_code' => 'Default',
                    'product_type' => 'giftcard',
                    '_attribute_set' => '123',
                    'name' => 'giftcard',
                    'giftcard_type' => 'virtual',
                    'giftcard_amount' => '123',
                    'giftcard_allow_open_amount' => '',
                    'giftcard_open_amount_min' => '',
                    'giftcard_open_amount_max' => '',
                    'giftcard_is_redeemable' => '0',
                    'giftcard_lifetime' => '1',
                    'giftcard_allow_message' => '1',
                    'giftcard_email_template' => 'Default'
                ]
            ],
            [
                'rowData' => [
                    'sku' => 'giftcardsku',
                    'attribute_set_code' => 'Default',
                    'product_type' => 'giftcard',
                    '_attribute_set' => '123',
                    'name' => 'giftcard',
                    'giftcard_type' => 'virtual',
                    'giftcard_amount' => '123',
                    'giftcard_allow_open_amount' => '',
                    'giftcard_open_amount_min' => '',
                    'giftcard_open_amount_max' => '',
                    'giftcard_lifetime' => '1',
                    'giftcard_allow_message' => '1',
                    'giftcard_email_template' => 'Default'
                ]
            ]
        ];
    }

    /**
     * Dataprovider for testSaveData()
     *
     * @return array
     */
    public function saveDataProvider(): array
    {
        return [
            [
                'bunch' => [
                    [
                        'sku' => 'giftcardsku1',
                        'product_type' => 'giftcard',
                        'name' => 'giftcard',
                        'giftcard_type' => 'virtual',
                        'giftcard_amount' => '100, 200',
                        'giftcard_allow_open_amount' => '',
                        'giftcard_open_amount_min' => '',
                        'giftcard_open_amount_max' => '',
                        'giftcard_is_redeemable' => '0',
                        'giftcard_lifetime' => '1',
                        'giftcard_allow_message' => '1',
                        'giftcard_email_template' => 'Default'
                    ],
                    [
                        'sku' => 'giftcardsku2',
                        'product_type' => 'giftcard',
                        'name' => 'giftcard',
                        'giftcard_type' => 'physical',
                        'giftcard_amount' => '',
                        'giftcard_allow_open_amount' => '1',
                        'giftcard_open_amount_min' => '100',
                        'giftcard_open_amount_max' => '200',
                        'giftcard_is_redeemable' => '1',
                        'giftcard_lifetime' => '6',
                        'giftcard_allow_message' => '0',
                        'giftcard_email_template' => 'Default'
                    ],
                    [
                        'sku' => 'giftcardsku3',
                        'product_type' => 'giftcard',
                        'name' => 'giftcard',
                        'giftcard_type' => 'combined',
                        'giftcard_amount' => '100, 200',
                        'giftcard_allow_open_amount' => '',
                        'giftcard_open_amount_min' => '',
                        'giftcard_open_amount_max' => '',
                        'giftcard_is_redeemable' => '',
                        'giftcard_lifetime' => '6',
                        'giftcard_allow_message' => '',
                        'giftcard_email_template' => 'Default'
                    ],
                ],
                'sku' => [
                    'giftcardsku1' => [
                        'entity_id' => '1',
                        'type_id' => 'giftcard',
                        'attr_set_id' => '4',
                        'attr_set_code' => 'Default'
                    ],
                    'giftcardsku2' => [
                        'entity_id' => '1',
                        'type_id' => 'giftcard',
                        'attr_set_id' => '4',
                        'attr_set_code' => 'Default'
                    ],
                    'giftcardsku3' => [
                        'entity_id' => '1',
                        'type_id' => 'giftcard',
                        'attr_set_id' => '4',
                        'attr_set_code' => 'Default'
                    ],
                ],
                'allowed' => true
            ],
            [
                'bunch' => [
                    [
                        'sku' => 'giftcardsku1',
                        'product_type' => 'giftcard',
                        'name' => 'giftcard',
                        'giftcard_type' => 'virtual',
                        'giftcard_amount' => '100, 200',
                        'giftcard_allow_open_amount' => '',
                        'giftcard_open_amount_min' => '',
                        'giftcard_open_amount_max' => '',
                        'giftcard_is_redeemable' => '',
                        'giftcard_lifetime' => '',
                        'giftcard_allow_message' => '',
                        'giftcard_email_template' => ''
                    ],
                ],
                'sku' => [
                    'giftcardsku1' => [
                        'entity_id' => '1',
                        'type_id' => 'giftcard',
                        'attr_set_id' => '4',
                        'attr_set_code' => 'Default'
                    ],
                ],
                'allowed' => false
            ],
        ];
    }

    /**
     * Dataprovider for testIsValid
     *
     * @return array
     */
    public function isValidDataProvider(): array
    {
        return [
            ['attributeId' => 123, 'amount' => '', 'result' => false],
            ['attributeId' => 123, 'amount' => '100', 'result' => true],
        ];
    }
}
