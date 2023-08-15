<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\Websites;
use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsitesTest extends TestCase
{
    /**
     * @var Websites
     */
    private $modifier;

    /**
     * @var MockObject
     */
    private $modifierMock;

    /**
     * @var MockObject
     */
    private $arrayMergerMock;

    protected function setUp(): void
    {
        $this->modifierMock = $this->createMock(\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites::class);
        $this->arrayMergerMock = $this->createMock(ArrayManager::class);
        $this->modifier = new Websites(
            $this->arrayMergerMock,
            $this->modifierMock
        );
    }

    public function testModifyMeta()
    {
        $meta = [
            'websites' => []
        ];
        $this->modifierMock->expects($this->once())->method('modifyMeta')->willReturn($meta);
        $this->arrayMergerMock->expects($this->once())->method('get')->with('websites', $meta)->willReturn(true);
        $this->arrayMergerMock
            ->expects($this->once())
            ->method('set')
            ->with('websites/arguments/data/config/disabled', $meta, true)
            ->willReturn($meta);
        $this->assertEquals($meta, $this->modifier->modifyMeta($meta));
    }
}
