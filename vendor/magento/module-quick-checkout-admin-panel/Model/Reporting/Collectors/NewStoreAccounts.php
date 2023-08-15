<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting\Collectors;

use Magento\Framework\App\ResourceConnection;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\DataCollectorInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportData;
use Psr\Log\LoggerInterface;
use Zend_Db_Statement_Exception;

class NewStoreAccounts implements DataCollectorInterface
{
    private const REPORT_SECTION_ID = 'new_accounts';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(ResourceConnection $resource, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Fetch the new accounts that matches the provided criteria
     *
     * @param Filters $filters
     * @return ReportData
     */
    public function collect(Filters $filters): ReportData
    {
        $data = [];

        $query = <<<'EOD'
            SELECT
                UNIX_TIMESTAMP(ce.created_at) as date,
                count(*) as new_accounts
            FROM
                customer_entity ce
            WHERE
                DATE(ce.created_at) BETWEEN DATE(:start_date) AND DATE(:end_date)
            GROUP BY
                DATE_FORMAT(ce.created_at, '%d %b %Y')
EOD;

        $res = $this->resource->getConnection()->query(
            $query,
            [
                'start_date' => $filters->getStartDate(),
                'end_date' => $filters->getEndDate(),
            ]
        );

        try {
            $data = $this->processData($res->fetchAll());
        } catch (Zend_Db_Statement_Exception $error) {
            $this->logger->error(
                'An unexpected error occurred while fetching the new store accounts',
                ['error' => $error->getMessage()]
            );
        }

        return new ReportData(self::REPORT_SECTION_ID, $data);
    }

    /**
     * Transforms data from string to int for date and new_accounts
     *
     * @param array $data
     * @return array
     */
    private function processData(array $data): array
    {
        return array_map(function ($account) {
            return [
                'date' => (int)$account['date'],
                'new_accounts' => (int)$account['new_accounts']
            ];
        }, $data);
    }
}
