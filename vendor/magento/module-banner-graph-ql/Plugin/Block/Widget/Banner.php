<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerGraphQl\Plugin\Block\Widget;

use Magento\Banner\Block\Widget\Banner as BannerWidget;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Plugin for widget with banner
 */
class Banner
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
     * After plugin for widget with banner
     *
     * @param BannerWidget $subject
     * @param $result
     * @return string
     */
    public function afterGetWidgetAttributes(BannerWidget $subject, $result): string
    {
        if (empty($subject->getBannerIds())) {
            $bannerUids = '';
        } else {
            $bannerUids = $this->getBannerUids($subject);
        }

        $result .= " data-uids=\"{$bannerUids}\"";
        return $result;
    }

    /**
     * @param BannerWidget$subject
     * @return string
     */
    private function getBannerUids(BannerWidget $subject): string
    {
        $uids = [];

        $bannerIds = explode(',', $subject->getBannerIds());
        foreach ($bannerIds as $bannerId) {
            $uids[] = $this->idEncoder->encode($bannerId);
        }

        return implode(',', $uids);
    }
}
