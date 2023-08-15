<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting;

interface DataCollectorInterface
{
    /**
     * Define the contract to collect data for a report
     *
     * @param Filters $filters
     * @return ReportData
     */
    public function collect(Filters $filters): ReportData;
}
