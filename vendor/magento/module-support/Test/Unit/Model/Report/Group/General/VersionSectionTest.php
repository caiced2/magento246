<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\General;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Group\General\VersionSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VersionSectionTest extends TestCase
{
    /**
     * @var VersionSection
     */
    protected $version;

    /**
     * @var ProductMetadataInterface|MockObject
     */
    protected $productMetaData;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->productMetaData = $this->getMockForAbstractClass(ProductMetadataInterface::class);

        $this->version = $this->objectManagerHelper->getObject(
            VersionSection::class,
            ['productMetadata' => $this->productMetaData]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $edition = 'Enterprise';
        $version = '1.0.0-beta';

        $expectedData = [
            VersionSection::REPORT_TITLE => [
                'headers' => ['Version'],
                'data' => ['Enterprise 1.0.0-beta']
            ]
        ];

        $this->productMetaData->expects($this->once())->method('getEdition')->willReturn($edition);
        $this->productMetaData->expects($this->once())->method('getVersion')->willReturn($version);

        $this->assertSame($expectedData, $this->version->generate());
    }
}
