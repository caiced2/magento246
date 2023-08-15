<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleImportExportStaging\Test\Unit\Plugin\BundleImportExport\Model\Import\Product\Type\Bundle;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\Selection;
use Magento\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver as Subject;
use Magento\BundleImportExportStaging\Plugin\BundleImportExport\Model\Import\Product\Type\Bundle\RelationsDataSaver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Sequence\SequenceManager;
use Magento\Framework\EntityManager\Sequence\SequenceRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RelationsDataSaverTest extends TestCase
{
    /**
     * @var Select
     */
    private $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var RelationsDataSaver
     */
    private $relationsDataSaver;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var SequenceManager|MockObject
     */
    private $sequenceManagerMock;

    /**
     * @var SequenceRegistry|MockObject
     */
    private $sequenceRegistryMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var Subject|MockObject
     */
    private $relationsDataSaverMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sequenceManagerMock = $this->getMockBuilder(SequenceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->relationsDataSaverMock = $this->getMockBuilder(Subject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataMock->expects($this->any())
            ->method('getEntityConnectionName')
            ->willReturn('test');

        $this->sequenceRegistryMock = $this->getMockBuilder(SequenceRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnectionByName')
            ->with('test')
            ->willReturn($this->connectionMock);
        $this->resourceConnectionMock->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $this->relationsDataSaver = $helper->getObject(
            RelationsDataSaver::class,
            [
                'metadataPool' => $this->metadataPoolMock,
                'sequenceManager' => $this->sequenceManagerMock,
                'sequenceRegistry' => $this->sequenceRegistryMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    public function testBeforeSaveOptions()
    {
        $identifierField = '1';
        $options = [
            0 => [
                0 => 'option1',
                $identifierField => ['option2']
            ],
            1 => [0 => 'option2']
        ];
        $generateIdentifier = 'generated_option';
        $optionsNew = $options;
        $entityType = OptionInterface::class;
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($this->metadataMock);

        $this->sequenceRegistryMock->expects($this->any())
            ->method('retrieve')
            ->with($entityType)
            ->willReturn(['sequenceTable' => 'sequence_table']);

        $this->metadataMock->expects($this->any())->method('getIdentifierField')->willReturn($identifierField);
        /* For the 0 element of the array the code inside else will be executed */
        $this->sequenceManagerMock->expects($this->any())
            ->method('force')
            ->with($entityType, $options[$identifierField]);
        $optionsNew[1][$identifierField] = $generateIdentifier;
        /* For the 1 element of the array the code inside if will be executed */
        $this->metadataMock->expects($this->any())->method('generateIdentifier')->willReturn($generateIdentifier);

        $this->assertEquals(
            [$optionsNew],
            $this->relationsDataSaver->beforeSaveOptions($this->relationsDataSaverMock, $options)
        );
    }

    public function testBeforeSaveSelections()
    {
        $identifierField = '1';
        $selections = [
            0 => [
                0 => 'selection1',
                $identifierField => ['selection2']
            ],
            1 => [0 => 'selection2']
        ];
        $generateIdentifier = 'generated_selection';
        $selectionsNew = $selections;
        $selectionsNew[1][$identifierField] = $generateIdentifier;

        $entityType = Selection::class;
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($this->metadataMock);

        $this->sequenceRegistryMock->expects($this->any())
            ->method('retrieve')
            ->with($entityType)
            ->willReturn(['sequenceTable' => 'sequence_table']);

        $this->metadataMock->expects($this->any())->method('getIdentifierField')->willReturn($identifierField);
        /* For the 0 element of the array the code inside else will be executed */
        $selectionsNew[1][$identifierField] = $generateIdentifier;
        $this->sequenceManagerMock->expects($this->any())
            ->method('force')
            ->with($entityType, $selections[$identifierField]);
        /*  For the 1 element of the array the code inside if will be executed */
        $this->metadataMock->expects($this->any())->method('generateIdentifier')->willReturn($generateIdentifier);

        $this->assertEquals(
            [$selectionsNew],
            $this->relationsDataSaver->beforeSaveSelections($this->relationsDataSaverMock, $selections)
        );
    }
}
