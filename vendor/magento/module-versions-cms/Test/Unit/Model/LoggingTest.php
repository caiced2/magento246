<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Logging\Model\Event;
use Magento\VersionsCms\Model\Logging;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoggingTest extends TestCase
{
    /**
     * @var Logging
     */
    protected $logging;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterface;

    /**
     * @var Event|MockObject
     */
    protected $eventModel;

    protected function setUp(): void
    {
        $this->requestInterface = $this->getMockForAbstractClass(RequestInterface::class);
        $this->eventModel = $this->getMockBuilder(Event::class)
            ->setMethods(['setInfo', '__sleep'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->logging = $this->objectManagerHelper->getObject(
            Logging::class,
            [
                'request' => $this->requestInterface
            ]
        );
    }

    public function testPostDispatchCmsHierachyView()
    {
        $this->eventModel->expects($this->once())->method('setInfo')->with('Tree Viewed')->willReturnSelf();
        $this->logging->postDispatchCmsHierachyView([], $this->eventModel);
    }
}
