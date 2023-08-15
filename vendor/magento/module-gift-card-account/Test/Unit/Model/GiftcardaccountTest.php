<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Model\EmailManagement;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardaccountTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Giftcardaccount
     */
    private $model;

    /**
     * @var EmailManagement|MockObject
     */
    private $emailManagement;

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->emailManagement = $this->getMockBuilder(EmailManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $this->objectManager->getObject(
            Giftcardaccount::class,
            [
                'emailManagement' => $this->emailManagement
            ]
        );
    }

    /**
     * @dataProvider sendEmailDataProvider
     * @param bool $sendEmail
     */
    public function testSendEmail($sendEmail)
    {
        $this->emailManagement->expects($this->atLeastOnce())->method('sendEmail')->with($this->model)
            ->willReturn($sendEmail);
        $this->model->sendEmail();
        $this->assertEquals($sendEmail, $this->model->getEmailSent());
    }

    /**
     * @return array
     */
    public function sendEmailDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
