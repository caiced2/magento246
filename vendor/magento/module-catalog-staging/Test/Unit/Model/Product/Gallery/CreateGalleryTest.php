<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product\Gallery;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\UpdateHandler;
use Magento\CatalogStaging\Model\Product\Gallery\CreateHandler;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateGalleryTest extends TestCase
{

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var CreateHandler
     */
    private $createHandler;

    /**
     * @var UpdateHandler|MockObject
     */
    private $updateHandlerMock;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->updateHandlerMock = $this->getMockBuilder(UpdateHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
    }

    /**
     * Test if UpdateHandler will be called instead of CreateHandler on existing products
     *
     * @throws LocalizedException
     */
    public function testExecute()
    {
        $this->productMock
            ->expects($this->any())
            ->method('isObjectNew')
            ->willReturn(false);

        $this->updateHandlerMock
            ->expects($this->once())
            ->method('execute');

        $this->createHandler = $this->objectManagerHelper->getObject(
            CreateHandler::class,
            ['updateHandler' => $this->updateHandlerMock]
        );

        $this->createHandler->execute($this->productMock);
    }
}
