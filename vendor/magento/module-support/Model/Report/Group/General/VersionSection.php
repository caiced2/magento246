<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\General;

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Magento Version report
 */
class VersionSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Magento Version';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($logger, $data);
        $this->productMetadata = $productMetadata;
    }

    /**
     * Generate Magento Version information
     *
     * @return array
     */
    public function generate()
    {
        return [
            self::REPORT_TITLE => [
                'headers' => ['Version'],
                'data' => [
                    $this->productMetadata->getEdition() . ' ' . $this->productMetadata->getVersion()
                ]
            ]
        ];
    }
}
