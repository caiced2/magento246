<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\Block;

use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\CmsStaging\Model\Block\DataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    public function testMetadataReplace()
    {
        $metadataProviderMock = $this->createMock(
            MetadataProvider::class
        );
        $collectionFactoryMock = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Block\CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(Collection::class));

        $metadataProviderMock->expects($this->once())->method('getMetadata')->willReturn(['key', 'value']);

        new DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactoryMock,
            $this->getMockForAbstractClass(DataPersistorInterface::class),
            $metadataProviderMock
        );
    }
}
