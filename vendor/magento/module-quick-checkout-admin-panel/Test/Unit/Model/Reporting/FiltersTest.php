<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Unit\Model\Reporting;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use PHPUnit\Framework\TestCase;

class FiltersTest extends TestCase
{
    public function testDateFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Invalid start date format');
        new Filters('20/11/1993', '10/5/2001');
    }

    public function testFutureDates()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Future dates are not allowed');
        $today = new DateTimeImmutable('now');
        $nextWeek = $today->add(new DateInterval('P1W'));
        new Filters($today->format(Filters::DATE_FORMAT), $nextWeek->format(Filters::DATE_FORMAT));
    }

    public function testInvalidDateRanges()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('The start date must be before the end date');
        new Filters('2021-05-10', '2021-04-10');
    }

    public function testAllowedIntervals()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('The maximum allowed interval is one year');
        new Filters('2019-05-10', '2021-05-10');
    }
}
