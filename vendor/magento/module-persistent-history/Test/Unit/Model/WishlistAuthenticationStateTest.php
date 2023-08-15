<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Model\WishlistAuthenticationState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WishlistAuthenticationStateTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $phHelperMock;

    /**
     * @var MockObject
     */
    protected $persistentSessionMock;

    /**
     * @var WishlistAuthenticationState
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->phHelperMock = $this->createPartialMock(
            Data::class,
            ['isWishlistPersist']
        );
        $this->persistentSessionMock = $this->createPartialMock(
            Session::class,
            ['isPersistent']
        );
        $this->subject = $objectManager->getObject(
            WishlistAuthenticationState::class,
            ['phHelper' => $this->phHelperMock, 'persistentSession' => $this->persistentSessionMock]
        );
    }

    public function testIsAuthEnabledIfPersistentSessionNotPersistent()
    {
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(false);
        $this->assertTrue($this->subject->isEnabled());
    }

    public function testIsAuthEnabledIfwishlistNotPersistent()
    {
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->phHelperMock->expects($this->once())->method('isWishlistPersist')->willReturn(false);
        $this->assertTrue($this->subject->isEnabled());
    }

    public function testIsAuthEnabled()
    {
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->phHelperMock->expects($this->once())->method('isWishlistPersist')->willReturn(true);
        $this->assertFalse($this->subject->isEnabled());
    }
}
