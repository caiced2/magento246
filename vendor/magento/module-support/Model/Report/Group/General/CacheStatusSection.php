<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\General;

/**
 * Cache Status report
 */
class CacheStatusSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Cache Status';

    /**
     * @var \Magento\Framework\App\Cache\TypeList
     */
    protected $typeList;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Cache\TypeList $typeList
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Cache\TypeList $typeList,
        array $data = []
    ) {
        parent::__construct($logger, $data);
        $this->typeList = $typeList;
    }

    /**
     * Generate Cache Status information
     *
     * @return array
     */
    public function generate()
    {
        $invalidatedCacheTypes = $this->typeList->getInvalidated();
        $cacheTypes = $this->typeList->getTypes();

        $data = $this->generateCacheStatusData($cacheTypes, $invalidatedCacheTypes);

        return [
            self::REPORT_TITLE => [
                'headers' => ['Cache', 'Status', 'Type', 'Associated Tags', 'Description'],
                'data' => $data
            ]
        ];
    }

    /**
     * Generate data for Cache Status report
     *
     * @param \Magento\Framework\DataObject[] $cacheTypes
     * @param array $invalidatedCacheTypes
     * @return array
     */
    protected function generateCacheStatusData(array $cacheTypes, array $invalidatedCacheTypes)
    {
        $data = [];

        foreach ($cacheTypes as $typeName => $type) {
            $data[] = [
                /** @var \Magento\Framework\DataObject $type */
                $type->getCacheType(),
                isset($invalidatedCacheTypes[$type->getId()])
                    ? 'Invalidated'
                    : ($type->getStatus() ? 'Enabled' : 'Disabled'),
                $typeName,
                $type->getTags(),
                $type->getDescription()
            ];
        }

        return $data;
    }
}
