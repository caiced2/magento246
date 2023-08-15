<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer;

use Magento\Customer\Api\MetadataInterface;
use Magento\CustomerCustomAttributes\Model\Customer\FileDownloadValidator;
use Magento\CustomerCustomAttributes\Model\Customer\TemporaryFileStorageInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

/**
 * Test file download validator
 */
class FileDownloadValidatorTest extends TestCase
{
    /**
     * @var TemporaryFileStorageInterface
     */
    private $storage;

    /**
     * @var MetadataInterface
     */
    private $metadata;

    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @var FileDownloadValidator
     */
    private $model;

    private $attributes = [
        'file_attr' => 'file',
        'image_attr' => 'image',
        'some_attr' => 'text',
    ];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = $this->createMock(TemporaryFileStorageInterface::class);
        $this->metadata = $this->createMock(MetadataInterface::class);
        $this->attributeFactory = $this->createMock(AttributeInterfaceFactory::class);
        $this->model = new FileDownloadValidator(
            $this->storage,
            $this->metadata,
            $this->attributeFactory,
            'customer'
        );
        $this->metadata->method('getAttributeMetadata')
            ->willReturnCallback(
                function ($attributeCode) {
                    if (isset($this->attributes[$attributeCode])) {
                        $mock = $this->createMock(\Magento\Customer\Api\Data\AttributeMetadataInterface::class);
                        $mock->method('getAttributeCode')
                            ->willReturn($attributeCode);
                        $mock->method('getFrontendInput')
                            ->willReturn($this->attributes[$attributeCode]);
                        return $mock;
                    }
                    throw new NoSuchEntityException();
                }
            );

        $this->attributeFactory->method('create')
            ->willReturnCallback(
                function ($params) {
                    $mock = $this->createMock(\Magento\Framework\Api\AttributeInterface::class);
                    $mock->method('getAttributeCode')
                        ->willReturn($params['data']['attribute_code']);
                    $mock->method('getValue')
                        ->willReturn($params['data']['value']);
                    return $mock;
                }
            );
    }

    /**
     * @param string $fileName
     * @param array $attributes
     * @param array $tmpFiles
     * @param bool $expected
     * @dataProvider canDownloadTemporaryFileDataProvider
     */
    public function testCanDownloadTemporaryFile(
        string $fileName,
        array $attributes,
        array $tmpFiles,
        bool $expected
    ): void {
        $this->storage->method('get')->willReturn($tmpFiles);
        foreach ($attributes as $key => $attribute) {
            $attributes[$key] = new AttributeValue($attribute);
        }
        $this->assertEquals($expected, $this->model->canDownloadFile($fileName, $attributes));
    }

    /**
     * @return array[]
     */
    public function canDownloadTemporaryFileDataProvider(): array
    {
        return [
            [
                '/i/m/image2.jpeg',
                [
                    [
                        'attribute_code' => 'file_attr',
                        'value' => '/f/i/file1.pdf',
                    ],
                    [
                        'attribute_code' => 'image_attr',
                        'value' => '/i/m/image2.jpeg',
                    ]
                ],
                [],
                true
            ],
            [
                '/f/i/file2.pdf',
                [
                    [
                        'attribute_code' => 'file_attr',
                        'value' => '/f/i/file1.pdf',
                    ],
                    [
                        'attribute_code' => 'image_attr',
                        'value' => '/i/m/image2.jpeg',
                    ]
                ],
                [
                    'customer' => [
                        'file_attr' => '/f/i/file2.pdf'
                    ]
                ],
                true
            ],
            [
                '/f/i/file3.pdf',
                [
                    [
                        'attribute_code' => 'file_attr',
                        'value' => '/f/i/file1.pdf',
                    ],
                    [
                        'attribute_code' => 'image_attr',
                        'value' => '/i/m/image2.jpeg',
                    ]
                ],
                [
                    'customer' => [
                        'file_attr' => '/f/i/file2.pdf'
                    ]
                ],
                false
            ],
            [
                '/f/i/file2.pdf',
                [
                    [
                        'attribute_code' => 'file_attr',
                        'value' => '/f/i/file1.pdf',
                    ],
                    [
                        'attribute_code' => 'image_attr',
                        'value' => '/i/m/image2.jpeg',
                    ]
                ],
                [
                    'customer' => [
                        'some_attr' => '/f/i/file2.pdf'
                    ]
                ],
                false
            ],
            [
                '/f/i/file2.pdf',
                [
                    [
                        'attribute_code' => 'file_attr',
                        'value' => '/f/i/file1.pdf',
                    ],
                    [
                        'attribute_code' => 'image_attr',
                        'value' => '/i/m/image2.jpeg',
                    ]
                ],
                [
                    'customer' => [
                        'invalid_attr' => '/f/i/file2.pdf'
                    ]
                ],
                false
            ],
        ];
    }
}
