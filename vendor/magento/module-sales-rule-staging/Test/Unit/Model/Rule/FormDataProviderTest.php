<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Rule;

use Magento\SalesRuleStaging\Model\Rule\FormDataProvider;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule\Metadata\ValueProvider;
use Magento\Staging\Api\UpdateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormDataProviderTest extends TestCase
{

    /**
     * @var \Magento\Framework\App\RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory|MockObject
     */
    private $salesRuleFactoryMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \Magento\Staging\Model\Entity\DataProvider\MetadataProvider|MockObject
     */
    private $metaDataProviderMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var \Magento\Framework\Registry|MockObject
     */
    private $registryMock;

    /**
     * @var ValueProvider|MockObject
     */
    private $metadataValueProviderMock;

    /**
     * @var FormDataProvider
     */
    private $model;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->metadataValueProviderMock = $this->createMock(ValueProvider::class);
        $this->metaDataProviderMock = $this->createMock(
            \Magento\Staging\Model\Entity\DataProvider\MetadataProvider::class
        );
        $this->metaDataProviderMock->expects($this->atLeastOnce())->method('getMetadata')->willReturn([]);
        $this->salesRuleFactoryMock = $this->createMock(\Magento\SalesRule\Model\RuleFactory::class);
        $this->requestMock = $this->getMockForAbstractClass(\Magento\Framework\App\RequestInterface::class);
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
    }

    /**
     * Test that FormDataProvider will use VersionManager to retrieve form data
     */
    public function testMetadataValuesUsesVersionManager()
    {
        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['update_id'], ['id']);
        $update = $this->getMockForAbstractClass(\Magento\Staging\Api\Data\UpdateInterface::class);
        $update->expects($this->once())->method('getId')->willReturn(1);
        $this->updateRepositoryMock->expects($this->once())->method('get')->willReturn($update);
        $this->versionManagerMock->expects($this->once())->method('setCurrentVersionId');
        $rule = $this->createMock(\Magento\SalesRule\Model\Rule::class);
        $rule->expects($this->once())->method('load');
        $this->salesRuleFactoryMock->expects($this->once())->method('create')->willReturn($rule);
        $this->metadataValueProviderMock->expects($this->once())->method('getMetadataValues')->willReturn([]);

        $this->model = new FormDataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $this->collectionFactoryMock,
            $this->registryMock,
            $this->metadataValueProviderMock,
            $this->metaDataProviderMock,
            $this->salesRuleFactoryMock,
            $this->requestMock,
            [],
            [],
            [],
            $this->versionManagerMock,
            $this->updateRepositoryMock
        );
    }
}
