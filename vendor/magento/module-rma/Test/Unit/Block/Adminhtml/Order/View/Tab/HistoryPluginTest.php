<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Order\View\Tab;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Order\View\Tab\HistoryPlugin;
use Magento\Rma\Model\ResourceModel\Rma\Collection;
use Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History as StatusHistory;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\History;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for History Plugin

 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryPluginTest extends TestCase
{
    /**
     * @var HistoryPlugin
     */
    private $historyPlugin;

    /**
     * @var Collection|MockObject
     */
    private $rmaCollection;

    /**
     * @var CollectionFactory|MockObject
     */
    private $historyCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->rmaCollection = $this->createMock(Collection::class);
        $this->rmaCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->historyCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->historyPlugin = $objectManager->getObject(
            HistoryPlugin::class,
            [
                'rmaCollection' => $this->rmaCollection,
                'historyCollectionFactory' => $this->historyCollectionFactory
            ]
        );
    }

    /**
     * @dataProvider afterGetFullHistoryProvider
     * @param array $returnsOptions
     * @param array $originalHistory
     */
    public function testAfterGetFullHistory(array $returnsOptions, array $originalHistory = [])
    {
        $rmaModelMock = $this->createMock(Rma::class);
        $rmaModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($returnsOptions['id']);
        $rmaModelMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($returnsOptions['id']);

        $this->rmaCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$rmaModelMock]));

        list(
            $historyCollection,
            $original,
            $expected,
            $comments
        ) = $this->getCase($returnsOptions, $originalHistory);

        $this->rmaCollection->expects($this->once())
            ->method('load')
            ->willReturn($this->rmaCollection);

        $this->rmaCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(2);

        $this->historyCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($historyCollection);
        $historyCollection->expects($this->once())
            ->method('getItems')
            ->willReturn($comments);

        $subject = $this->createPartialMock(History::class, ['getOrder']);
        $subject->expects($this->once())
            ->method('getOrder')->willReturnSelf();

        $this->assertEquals($expected, $this->historyPlugin->afterGetFullHistory($subject, $original));
    }

    /**
     * Data provider full history
     *
     * @return array
     */
    public function afterGetFullHistoryProvider(): array
    {
        return [
            [
                ['id' => 42, 'is_customer_notified' => true],
            ],
            [
                ['id' => 1, 'is_customer_notified' => false],
            ],
            [
                ['id' => 42, 'is_customer_notified' => true]
            ],
            [
                ['id' => 1, 'is_customer_notified' => false],
                [
                    [
                        'title' => 'Shipping #1000007 created',
                        'notified' => false,
                        'comment' => '',
                        'created_at' => (new \DateTime('now', new \DateTimeZone('UTC'))),
                    ]
                ]
            ]
        ];
    }

    /**
     *
     * Return case for test
     *
     * @return array
     */
    private function getCase($returnsOptions, $originalHistory = []): array
    {
        $expected = $originalHistory;
        $rmaId = $returnsOptions['id'];
        $rma = $this->createPartialMock(Rma::class, ['getId', 'getIncrementId']);
        $rma->expects($this->any())
            ->method('getId')
            ->willReturn($rmaId);

        $historyCollection = $this->createMock(\Magento\Rma\Model\ResourceModel\Rma\Status\History\Collection::class);

        $isCustomerNotified = $returnsOptions['is_customer_notified'];
        $createdAtDate = (new \DateTime('now', new \DateTimeZone('UTC')));

        $systemComment = $this->getSystemComment($isCustomerNotified, $createdAtDate, $rmaId);
        $customComment = $this->getCustomComment();
        $comments = [$systemComment, $customComment];
        $historyCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('rma_entity_id', ['in' => [$rmaId]])
            ->willReturn($comments);

        $expected[] = [
            'title' => sprintf('Return #%s created', $rmaId),
            'notified' => $isCustomerNotified,
            'comment' => '',
            'created_at' => $createdAtDate,
        ];

        usort($expected, [History::class, 'sortHistoryByTimestamp']);

        return [$historyCollection, $originalHistory, $expected, $comments];
    }

    /**
     * Return system comment
     */
    private function getSystemComment($isCustomerNotified, $createdAtDate, $rmaId): MockObject
    {
        $comment = $this->getMockBuilder(\Magento\Rma\Model\Rma\Status\History::class)->addMethods(
            ['getIsCustomerNotified']
        )
            ->onlyMethods(['getComment', 'getRmaEntityId', 'getCreatedAtDate'])
            ->disableOriginalConstructor()
            ->getMock();
        $comment->expects($this->once())
            ->method('getComment')
            ->willReturn(StatusHistory::getSystemCommentByStatus(Status::STATE_PENDING));
        $comment->expects($this->once())
            ->method('getIsCustomerNotified')
            ->willReturn($isCustomerNotified);
        $comment->expects($this->once())
            ->method('getCreatedAtDate')
            ->willReturn($createdAtDate);
        $comment->expects($this->once())
            ->method('getRmaEntityId')
            ->willReturn($rmaId);
        return $comment;
    }

    /**
     * Return custom comment
     */
    private function getCustomComment()
    {
        $comment = $this->getMockBuilder(\Magento\Rma\Model\Rma\Status\History::class)->addMethods(
            ['getIsCustomerNotified']
        )
            ->onlyMethods(['getComment', 'getCreatedAtDate'])
            ->disableOriginalConstructor()
            ->getMock();
        $comment->expects($this->once())
            ->method('getComment')
            ->willReturn('another comment');
        $comment->expects($this->never())
            ->method('getIsCustomerNotified');
        $comment->expects($this->never())
            ->method('getCreatedAtDate');
        return $comment;
    }
}
