<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\General;

/**
 * Index Status report
 */
class IndexStatusSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Index Status';

    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory
     */
    protected $indexerFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Indexer\Model\Indexer\CollectionFactory $indexerFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        array $data = []
    ) {
        parent::__construct($logger, $data);
        $this->indexerFactory = $indexerFactory;
        $this->timeZone = $timeZone;
    }

    /**
     * Generate Index Status information
     *
     * @return array
     */
    public function generate()
    {
        $indexers = $this->indexerFactory->create()->getItems();

        $data = $this->generateIndexStatusData($indexers);

        return [
            self::REPORT_TITLE => [
                'headers' => ['Index', 'Status', 'Update Required', 'Updated At', 'Mode', 'Is Visible', 'Description'],
                'data' => $data
            ]
        ];
    }

    /**
     * Generate data for Index Status report
     *
     * @param \Magento\Indexer\Model\IndexerInterface[] $indexers
     * @return array
     */
    protected function generateIndexStatusData(array $indexers)
    {
        $data = [];

        foreach ($indexers as $indexer) {
            $mode = $indexer->getView()->isEnabled() ? 'Update When Scheduled' : 'Update On Save';
            $visible = 'n/a';
            $latestUpdated = $this->timeZone->formatDateTime(
                new \DateTime($indexer->getLatestUpdated()),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::MEDIUM
            );
            $data[] = [
                (string) $indexer->getTitle(),
                $indexer->getStatus(),
                $indexer->isValid() ? 'No' : 'Yes',
                $indexer->getLatestUpdated() ? $latestUpdated : 'Never',
                $mode,
                $visible,
                (string) $indexer->getDescription()
            ];
        }

        return $data;
    }
}
