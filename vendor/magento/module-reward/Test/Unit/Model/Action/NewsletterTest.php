<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Action\Newsletter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewsletterTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var MockObject
     */
    protected $collFactoryMock;

    /**
     * @var Newsletter
     */
    protected $model;

    protected function setUp(): void
    {
        $this->rewardDataMock = $this->createMock(Data::class);
        $this->collFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Newsletter::class,
            ['rewardData' => $this->rewardDataMock, 'subscribersFactory' => $this->collFactoryMock]
        );
    }

    public function testGetPoints()
    {
        $websiteId = 100;
        $this->rewardDataMock->expects($this->once())
            ->method('getPointsConfig')
            ->with('newsletter', $websiteId)
            ->willReturn(500);
        $this->assertEquals(500, $this->model->getPoints($websiteId));
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     */
    public function testGetHistoryMessage(array $args, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->model->getHistoryMessage($args));
    }

    /**
     * @return array
     */
    public function getHistoryMessageDataProvider()
    {
        return [
            [
                'args' => [],
                'expectedResult' => 'Signed up for newsletter with email ',
            ],
            [
                'args' => ['email' => 'test@mail.com'],
                'expectedResult' => 'Signed up for newsletter with email test@mail.com'
            ]
        ];
    }
}
