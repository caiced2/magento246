<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting;

use Exception;
use Psr\Log\LoggerInterface;

class ReportingService
{
    /**
     * @var DataCollectorInterface[]
     */
    private array $collectors;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * ReportingService class constructor
     *
     * @param DataCollectorInterface[] $collectors
     * @param LoggerInterface $logger
     */
    public function __construct(array $collectors, LoggerInterface $logger)
    {
        $this->collectors = $collectors;
        $this->logger = $logger;
    }

    /**
     * Generates a report based on all the configured providers
     *
     * @param Filters $filters
     * @return array
     */
    public function generate(Filters $filters): array
    {
        $result = [];

        foreach ($this->collectors as $dataCollector) {
            try {
                $data = $dataCollector->collect($filters);
                $section = $data->getSection();
                if (isset($result[$section])) {
                    $this->logger->warning('Duplicated report section', ['section' => $section]);
                    continue;
                }
                $result[$section] = $data->getContent();
            } catch (Exception $error) {
                $this->logger->error(
                    'Unable to collect the reporting data',
                    [
                        'collector' => get_class($dataCollector),
                        'error' => $error->getMessage()
                    ]
                );
            }
        }

        return $result;
    }
}
