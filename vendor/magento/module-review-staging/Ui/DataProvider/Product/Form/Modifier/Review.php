<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ReviewStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Review\Ui\DataProvider\Product\Form\Modifier\Review as ReviewModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

class Review extends AbstractModifier
{
    /**
     * @var ReviewModifier
     */
    private $reviewModifier;

    /**
     * @param ReviewModifier $reviewModifier
     */
    public function __construct(ReviewModifier $reviewModifier)
    {
        $this->reviewModifier = $reviewModifier;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        return $this->reviewModifier->modifyData($data);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        $modifiedMeta = $this->reviewModifier->modifyMeta($meta);

        if (!isset($modifiedMeta[ReviewModifier::GROUP_REVIEW])) {
            return $modifiedMeta;
        }

        $listingConfig =
            $modifiedMeta[ReviewModifier::GROUP_REVIEW]['children']['review_listing']['arguments']['data']['config'];

        $listingConfig['dataScope'] = 'stagingreview_listing';
        $listingConfig['externalProvider'] = 'stagingreview_listing.stagingreview_listing_data_source';
        $listingConfig['selectionsProvider'] = 'stagingreview_listing.stagingreview_listing.product_columns.ids';
        $listingConfig['ns'] = 'stagingreview_listing';

        $modifiedMeta[ReviewModifier::GROUP_REVIEW]['children'] = [
            'stagingreview_listing' => [
                'arguments' => [
                    'data' => [
                        'config' => $listingConfig
                    ]
                ]
            ]
        ];

        return $modifiedMeta;
    }
}
