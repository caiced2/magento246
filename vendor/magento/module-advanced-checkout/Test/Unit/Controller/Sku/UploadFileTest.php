<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Controller\Sku;

use Magento\AdvancedCheckout\Controller\Sku\UploadFile;
use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UploadFileTest extends TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Test\Unit\Controller\Sku\UploadFile
     */
    protected $controller;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->requestMock = $this->createMock(Http::class);

        $args = [
            'request' => $this->requestMock,
            'objectManager' => $this->objectManagerMock
        ];

        $this->controller = $helper->getObject(UploadFile::class, $args);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $isSkuFileUploaded
     * @param int $processSkuFileCall
     * @param array $postItems
     * @param array|null $csvItems
     * @param array $expectedResult
     */
    public function testExecute($isSkuFileUploaded, $processSkuFileCall, $postItems, $csvItems, $expectedResult)
    {
        $helperMock = $this->createMock(Data::class);

        $this->objectManagerMock->expects($this->once())->method('get')
            ->with(Data::class)->willReturn($helperMock);

        $helperMock->expects($this->any())->method('isSkuFileUploaded')
            ->with($this->requestMock)->willReturn($isSkuFileUploaded);
        $helperMock->expects($this->exactly($processSkuFileCall))->method('processSkuFileUploading')
            ->willReturn($csvItems);

        $this->requestMock->expects($this->any())->method('getPost')->with('items')
            ->willReturn($postItems);
        $this->requestMock->expects($this->once())->method('setParam')->with('items', $expectedResult);

        $this->controller->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'isSkuFileUploaded' => true,
                'processSkuFileCall' => 1,
                'postItems' => ['postSku'],
                'csvItems' => ['fileSku'],
                'expectedResult' => ['postSku', 'fileSku']
            ],
            [
                'isSkuFileUploaded' => false,
                'processSkuFileCall' => 0,
                'postItems' => ['postSku'],
                'csvItems' => ['fileSku'],
                'expectedResult' => ['postSku']
            ],
            [
                'isSkuFileUploaded' => false,
                'processSkuFileCall' => 0,
                'postItems' => [],
                'csvItems' => null,
                'expectedResult' => []
            ],
            [
                'isSkuFileUploaded' => true,
                'processSkuFileCall' => 1,
                'postItems' => [],
                'csvItems' => ['fileSku'],
                'expectedResult' => ['fileSku']
            ],
        ];
    }
}
