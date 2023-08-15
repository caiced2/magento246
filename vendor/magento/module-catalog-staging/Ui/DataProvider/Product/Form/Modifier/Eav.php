<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as EavModifier;

class Eav extends EavModifier
{
    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = parent::modifyMeta($meta);
        $meta['product-details']['arguments']['data']['config']['sortOrder'] = 3;
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $data = parent::modifyData($data);
        $model = $this->locator->getProduct();

        if (isset($data[$model->getId()]['product']['news_from_date'])) {
            $data[$model->getId()]['product']['is_new'] = '1';
        };

        return $data;
    }
}
