<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MsrpStaging\Ui\DataProvider\Product\Form\Modifier;

/**
 * Msrp UI modifier for staging
 */
class MsrpStaging extends \Magento\Msrp\Ui\DataProvider\Product\Form\Modifier\Msrp
{
    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->msrpConfig->setStoreId($this->locator->getStore()->getId());
        return parent::modifyMeta($meta);
    }
}
