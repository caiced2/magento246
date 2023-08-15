<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Invitation\Block\Link;
use Magento\Invitation\Helper\Data;
use Magento\Invitation\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $_objectManagerHelper;

    protected function setUp(): void
    {
        $this->_objectManagerHelper = new ObjectManager($this);
    }

    public function testGetHref()
    {
        $url = 'http://test.exmaple.com/test';

        $invitationHelper = $this->getMockBuilder(
            Data::class
        )->disableOriginalConstructor()
            ->getMock();

        $invitationHelper->expects(
            $this->once()
        )->method(
            'getCustomerInvitationFormUrl'
        )->willReturn(
            $url
        );

        $block = $this->_objectManagerHelper->getObject(
            Link::class,
            ['invitationHelper' => $invitationHelper]
        );
        $this->assertEquals($url, $block->getHref());
    }

    /**
     * @return array
     */
    public static function dataForToHtmlTest()
    {
        return [[true, false], [false, true], [false, false]];
    }

    /**
     * @dataProvider dataForToHtmlTest
     * @param bool $isLoggedIn
     * @param bool $isEnabledOnFront
     */
    public function testToHtml($isLoggedIn, $isEnabledOnFront)
    {
        /** @var Session|MockObject $customerSession */
        $customerSession = $this->getMockBuilder(
            Session::class
        )->disableOriginalConstructor()
            ->getMock();

        /** @var Config|MockObject $invitationConfig */
        $invitationConfig = $this->getMockBuilder(
            Config::class
        )->disableOriginalConstructor()
            ->getMock();

        /** @var Link $block */
        $block = $this->_objectManagerHelper->getObject(
            Link::class,
            ['customerSession' => $customerSession, 'invitationConfiguration' => $invitationConfig]
        );

        $customerSession->expects($this->any())->method('isLoggedIn')->willReturn($isLoggedIn);

        $invitationConfig->expects(
            $this->any()
        )->method(
            'isEnabledOnFront'
        )->willReturn(
            $isEnabledOnFront
        );

        $this->assertEquals('', $block->toHtml());
    }
}
