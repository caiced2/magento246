<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerGraphQl\Model;

use Magento\Banner\Model\Banner;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Formatter for dynamic block
 */
class DynamicBlockFormatter
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param Uid $idEncoder
     */
    public function __construct(Uid $idEncoder)
    {
        $this->idEncoder = $idEncoder;
    }

    /**
     * Format dynamic block output
     *
     * @param Banner $dynamicBlock
     * @return array
     */
    public function format(Banner $dynamicBlock): array
    {
        return [
            'uid' => $this->idEncoder->encode($dynamicBlock->getBannerId()),
            'content' => [
                'html' => $dynamicBlock->getBannerContent()
            ]
        ];
    }
}
