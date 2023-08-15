<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

class Filters
{
    public const DATE_FORMAT = 'Y-m-d';

    /**
     * @var string
     */
    private string $startDate;

    /**
     * @var string
     */
    private string $endDate;

    /**
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(string $startDate, string $endDate)
    {
        $this->assertValidDates($startDate, $endDate);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Asserts that the provided dates are valid
     *
     * @param string $start
     * @param string $end
     * @return void
     */
    private function assertValidDates(string $start, string $end)
    {
        $startDate = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $start);

        if (!$startDate) {
            throw new InvalidArgumentException('Invalid start date format');
        }

        $endDate = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $end);

        if (!$endDate) {
            throw new InvalidArgumentException('Invalid end date format');
        }

        $now = new DateTimeImmutable('now');

        if ($startDate > $now || $endDate > $now) {
            throw new InvalidArgumentException('Future dates are not allowed');
        }

        if ($startDate > $endDate) {
            throw new InvalidArgumentException('The start date must be before the end date');
        }

        $minDate = $endDate->sub(new DateInterval('P1Y'));

        if ($startDate < $minDate) {
            throw new InvalidArgumentException('The maximum allowed interval is one year');
        }
    }

    /**
     * Returns the start date
     *
     * @return string
     */
    public function getStartDate(): string
    {
        return $this->startDate;
    }

    /**
     * Returns the end date
     *
     * @return string
     */
    public function getEndDate(): string
    {
        return $this->endDate;
    }
}
