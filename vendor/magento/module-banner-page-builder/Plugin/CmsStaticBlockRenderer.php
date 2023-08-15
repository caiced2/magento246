<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Plugin;

use Magento\PageBuilder\Model\Stage\Renderer\CmsStaticBlock;

/**
 * Replaces dynamic blocks with a placeholder for block output on stage
 */
class CmsStaticBlockRenderer
{
    /**
     * @var \Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock\PlaceholderFilter
     */
    private $placeholderFilter;

    /**
     * @param \Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock\PlaceholderFilter $placeholderFilter
     */
    public function __construct(
        \Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock\PlaceholderFilter $placeholderFilter
    ) {
        $this->placeholderFilter = $placeholderFilter;
    }

    /**
     * Converts dynamic blocks to placeholders in the output of the renderer
     * @param CmsStaticBlock $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRender(CmsStaticBlock $subject, array $result): array
    {
        if (empty($result['content'])) {
            return $result;
        }

        $result['content'] = $this->placeholderFilter->addPlaceholders($result['content']);

        return $result;
    }
}
