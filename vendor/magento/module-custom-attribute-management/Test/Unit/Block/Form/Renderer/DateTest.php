<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomAttributeManagement\Test\Unit\Block\Form\Renderer;

use Magento\CustomAttributeManagement\Block\Form\Renderer\Date as DateBlock;
use Magento\Eav\Model\Attribute;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Html\Date as DateView;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    /**
     * @var DateBlock
     */
    private $block;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDateMock;

    /**
     * @var DateView|MockObject
     */
    private $dateElement;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var Repository|MockObject
     */
    private $assetRepo;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $this->localeDateMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $contextMock->expects($this->once())
            ->method('getLocaleDate')
            ->willReturn($this->localeDateMock);

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetRepo = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $contextMock->expects($this->any())
            ->method('getAssetRepository')
            ->willReturn($this->assetRepo);

        $this->dateElement = $this->getMockBuilder(DateView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            DateBlock::class,
            [
                'context' => $contextMock,
                'dateElement' => $this->dateElement,
            ]
        );
    }

    /**
     * Test to Return field HTML
     */
    public function testGetFieldHtml()
    {
        $testResult = '<input type="date" value="">';

        $this->request->expects($this->any())
            ->method('isSecure')
            ->willReturn(false);

        $this->dateElement->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->dateElement->expects($this->once())
            ->method('getHtml')
            ->willReturn($testResult);

        // PHP 8.1 compatibility
        $this->localeDateMock
            ->method('getDateFormatWithLongYear')
            ->willReturn('');

        $this->block->setAttributeObject(
            $this->getMockBuilder(Attribute::class)
                ->disableOriginalConstructor()
                ->getMock()
        );
        $this->block->setEntity(
            $this->getMockBuilder(
                AbstractModel::class
            )->disableOriginalConstructor()
                ->getMock()
        );
        $this->assertEquals($testResult, $this->block->getFieldHtml());
    }

    /**
     * Test to get format which will be applied for date
     */
    public function testGetDateFormat()
    {
        $this->localeDateMock->expects($this->once())
            ->method('getDateFormatWithLongYear')
            ->willReturn('d/M/Y GGGG');

        $this->assertEquals(
            'd/M/Y',
            $this->block->getDateFormat()
        );
    }

    /**
     * Test for stored date inputs getter
     *
     * @param string $expected
     * @param array $data
     * @dataProvider getSortedDateInputsDataProvider
     */
    public function testGetSortedDateInputs($expected, array $data)
    {
        $this->localeDateMock->expects($this->once())
            ->method('getDateFormat')
            ->willReturn($data['format']);

        foreach ($data['date_inputs'] as $code => $html) {
            $this->block->setDateInput($code, $html);
        }
        $this->assertEquals($expected, $this->block->getSortedDateInputs($data['strip_non_input_chars']));
    }

    /**
     * @return array
     */
    public function getSortedDateInputsDataProvider()
    {
        return [
            [
                '<y><d><d><m>',
                [
                    'strip_non_input_chars' => true,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => 'y--d--e--m'
                ],
            ],
            [
                '<y>--<d>--<d>--<m>',
                [
                    'strip_non_input_chars' => false,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => 'y--d--e--m'
                ]
            ],

            [
                '<m><d><d><y>',
                [
                    'strip_non_input_chars' => true,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => '[medy]'
                ]
            ],
            [
                '[<m><d><d><y>]',
                [
                    'strip_non_input_chars' => false,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => '[medy]'
                ]
            ]
        ];
    }
}
