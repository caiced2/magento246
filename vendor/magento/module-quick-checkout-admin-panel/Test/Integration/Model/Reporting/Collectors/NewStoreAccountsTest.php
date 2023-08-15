<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Integration\Model\Reporting\Collectors;

use DateInterval;
use DateTime;
use Magento\Framework\ObjectManagerInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Collectors\NewStoreAccounts;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NewStoreAccountsTest extends TestCase
{
    private const EXPECTED_NUMBER_OF_ACCOUNTS = 2;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var NewStoreAccounts
     */
    private $newStoreAccounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->newStoreAccounts = $this->objectManager->get(NewStoreAccounts::class);
    }

    /**
     * @magentoDataFixture Magento_QuickCheckoutAdminPanel::Test/Integration/_files/importstoreaccounts.php
     */
    public function testCollect(): void
    {
        $fiveMonthsAgo = (new DateTime('now'))
            ->sub(new DateInterval('P5M'))
            ->format('Y-m-d');
        $tenDaysAgo = (new DateTime('now'))
            ->sub(new DateInterval('P10D'))
            ->format('Y-m-d');
        $filters = new Filters($fiveMonthsAgo, $tenDaysAgo, null, null);
        $result = $this->newStoreAccounts->collect($filters);
        $this->assertCount(self::EXPECTED_NUMBER_OF_ACCOUNTS, $result->getContent());
    }
}
