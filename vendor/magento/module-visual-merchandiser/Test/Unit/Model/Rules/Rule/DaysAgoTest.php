<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model\Rules\Rule;

use DateTime;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Model\Rules\Rule\DaysAgo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DaysAgoTest extends TestCase
{
    /**
     * @var Attribute|MockObject
     */
    protected $attribute;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $localeDate;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->localeDate->expects($this->once())->method('date')->willReturn($this->currentDate());
    }

    /**
     * Tests the method applyToCollection
     *
     * @param int $value
     * @param string $startOperator
     * @param string $endOperator
     * @param string $result
     * @param string $attribute
     *
     * @return void
     * @dataProvider applyToCollectionDataProvider
     */
    public function testApplyToCollection(
        $value,
        $startOperator,
        $endOperator = null,
        $result = null,
        $attribute = 'attribute'
    ): void {
        $model = (new ObjectManager($this))->getObject(
            DaysAgo::class,
            [
                'rule' => ['value' => $value, 'operator' => $startOperator, 'attribute' => $attribute],
                'attribute' => $this->attribute
            ]
        );

        $managerHelper = new ObjectManager($this);
        $managerHelper->setBackwardCompatibleProperty($model, 'localeDate', $this->localeDate);

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $date = $this->currentDate();
        $date = $date->modify('-' . $value . ' days');

        if ($startOperator != 'eq') {
            $dateResult = $date->format($result);

            $this->localeDate
                ->expects($this->once())
                ->method('convertConfigTimeToUtc')
                ->with($dateResult)
                ->willReturn('convertConfigTimeToUtcResult');

            $collection
                ->expects($this->once())
                ->method('addFieldToFilter')
                ->with($attribute, [$endOperator => 'convertConfigTimeToUtcResult']);
        } else {
            $this->localeDate
                ->method('convertConfigTimeToUtc')
                ->withConsecutive([$date->format('Y-m-d 00:00:00')], [$date->format('Y-m-d 23:59:59')])
                ->willReturnOnConsecutiveCalls(
                    'convertConfigTimeToUtcResultStartDate',
                    'convertConfigTimeToUtcResultEndDate'
                );

            $collection
                ->expects($this->once())
                ->method('addFieldToFilter')
                ->with(
                    $attribute,
                    [
                        'from' => 'convertConfigTimeToUtcResultStartDate',
                        'to' => 'convertConfigTimeToUtcResultEndDate'
                    ]
                );
        }

        $model->applyToCollection($collection);
    }

    /**
     * @return array
     */
    public function applyToCollectionDataProvider(): array
    {
        return [
            [1, 'lt', 'gt', 'Y-m-d 23:59:59'],
            [2, 'gt', 'lt', 'Y-m-d 00:00:00'],
            [3, 'gteq', 'lteq', 'Y-m-d 23:59:59'],
            [4, 'lteq', 'gteq', 'Y-m-d 00:00:00'],
            [5, 'eq']
        ];
    }

    /**
     * @return DateTime
     */
    private function currentDate(): DateTime
    {
        return new DateTime('2000-10-10', new \DateTimeZone(date_default_timezone_get()));
    }
}
