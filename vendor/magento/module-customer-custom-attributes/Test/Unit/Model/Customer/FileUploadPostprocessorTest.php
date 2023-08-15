<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer;

use Magento\CustomerCustomAttributes\Model\Customer\FileUploadPostprocessor;
use Magento\CustomerCustomAttributes\Model\Customer\TemporaryFileStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test file upload postprocessor
 */
class FileUploadPostprocessorTest extends TestCase
{
    /**
     * @var TemporaryFileStorageInterface
     */
    private $storage;

    /**
     * @var FileUploadPostprocessor
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = $this->createMock(TemporaryFileStorageInterface::class);
        $this->model = new FileUploadPostprocessor(
            $this->storage,
            'customer'
        );
    }

    /**
     * @param string $attributeCode
     * @param string $file
     * @param array $expected
     * @dataProvider processDataProvider
     */
    public function testProcess(string $attributeCode, string $file, array $expected): void
    {
        $tmpFiles = [
            'customer' => [
                'file_attr' => '/f/i/file1.pdf',
                'image_attr' => '/i/m/image1.jpeg'
            ],
            'customer_address' => [
                'file_attr' => '/f/i/file2.pdf'
            ]
        ];
        $this->storage->method('get')
            ->willReturn($tmpFiles);
        $this->storage->expects($this->once())
            ->method('set')
            ->with($expected);
        $this->model->process($attributeCode, $file);
    }

    /**
     * @return array
     */
    public function processDataProvider(): array
    {
        return [
            [
                'file_attr',
                '/f/i/file2.pdf',
                [
                    'customer' => [
                        'file_attr' => '/f/i/file2.pdf',
                        'image_attr' => '/i/m/image1.jpeg'
                    ],
                    'customer_address' => [
                        'file_attr' => '/f/i/file2.pdf'
                    ]
                ],
            ],
            [
                'file_attr_2',
                '/f/i/file3.pdf',
                [
                    'customer' => [
                        'file_attr' => '/f/i/file1.pdf',
                        'image_attr' => '/i/m/image1.jpeg',
                        'file_attr_2' => '/f/i/file3.pdf',
                    ],
                    'customer_address' => [
                        'file_attr' => '/f/i/file2.pdf'
                    ]
                ],
            ]
        ];
    }
}
