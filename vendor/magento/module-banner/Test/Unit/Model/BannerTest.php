<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model;

use Magento\Banner\Model\Banner;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Validation\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Validator\HTML\WYSIWYGValidatorInterface;
use Magento\Banner\Model\ResourceModel\Banner as ResourceModel;

class BannerTest extends TestCase
{
    /**
     * @var Banner
     */
    protected $banner;

    /**
     * @var WYSIWYGValidatorInterface|MockObject
     */
    private $wysiwygValidatorMock;

    /**
     * @var ResourceModel|MockObject
     */
    private $resourceMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->wysiwygValidatorMock = $this->getMockForAbstractClass(WYSIWYGValidatorInterface::class);
        $this->resourceMock = $this->createMock(ResourceModel::class);
        $this->banner = $objectManager->getObject(
            Banner::class,
            ['wysiwygValidator' => $this->wysiwygValidatorMock, 'resource' => $this->resourceMock]
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->banner = null;
    }

    public function testGetIdentities()
    {
        $id = 1;
        $this->banner->setId($id);
        $this->assertEquals(
            [Banner::CACHE_TAG . '_' . $id],
            $this->banner->getIdentities()
        );
    }

    public function testBeforeSave()
    {
        $this->resourceMock->expects($this->any())->method('getStoreContents')->willReturn([]);
        $this->banner->setName('Test');
        $this->banner->setId(1);
        $this->banner->setStoreContents([
            0 => '<p>{{widget type="Magento\Banner\Block\Widget\Banner" banner_ids="2"}}</p>'
        ]);
        $this->assertEquals($this->banner, $this->banner->beforeSave());
    }

    public function testBeforeSaveWithSameId()
    {
        $this->resourceMock->expects($this->any())->method('getStoreContents')->willReturn([]);
        $this->banner->setName('Test');
        $this->banner->setId(1);
        $this->banner->setStoreContents([
            0 => '<p>{{widget type="Magento\Banner\Block\Widget\Banner" banner_ids="1,2"}}</p>'
        ]);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            (string)__('Make sure that dynamic blocks rotator does not reference the dynamic block itself.')
        );
        $this->banner->beforeSave();
    }

    /**
     * Variation for content validation testing.
     *
     * @return array
     */
    public function getContentValidationCases(): array
    {
        return [
            'new-valid' => [['html1', 'html2'], [true, true], [], false, true],
            'new-invalid' => [['html1', 'html2'], [true, false], [], false, false],
            'existing-unchanged' => [['html1', 'html2'], [false, false], ['html1', 'html2'], true, true],
            'existing-invalid-changed' => [['html1', 'html2'], [false, false], ['html1', 'htmlOld'], true, false],
            'existing-valid-changed' => [['html1', 'html2'], [true, true], ['htmlOld1', 'htmlOld2'], true, true]
        ];
    }

    /**
     * Test content validation.
     *
     * @param array $contents
     * @param array $validMap
     * @param array $origContents
     * @param bool $hasId
     * @param bool $expectedValid
     * @return void
     * @dataProvider getContentValidationCases
     */
    public function testBeforeSaveContent(
        array $contents,
        array $validMap,
        array $origContents,
        bool $hasId,
        bool $expectedValid
    ): void {
        $this->resourceMock->expects($this->any())->method('getStoreContents')->willReturn($origContents);
        $this->wysiwygValidatorMock->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (string $content) use ($contents, $validMap): void {
                    $storeId = array_search($content, $contents, true);
                    if (array_key_exists($storeId, $validMap) && !$validMap[$storeId]) {
                        throw new ValidationException(__('Invalid content'));
                    }
                }
            );
        if ($hasId) {
            $this->banner->setId(1);
        }
        $this->banner->setName('test');
        $this->banner->setData('store_contents', $contents);

        try {
            $this->banner->beforeSave();
            $actuallyValid = true;
        } catch (LocalizedException $exception) {
            $actuallyValid = false;
        }
        $this->assertEquals($expectedValid, $actuallyValid);
    }
}
