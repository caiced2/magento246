<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\Stdlib\ArrayManager;

class Websites implements ModifierInterface
{
    /**
     * @var \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites
     */
    private $modifier;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     * @param \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites $modifier
     */
    public function __construct(
        ArrayManager $arrayManager,
        \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Websites $modifier
    ) {
        $this->arrayManager = $arrayManager;
        $this->modifier = $modifier;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->modifier->modifyMeta($meta);
        if ($this->arrayManager->get('websites', $meta)) {
            $meta = $this->arrayManager->set('websites/arguments/data/config/disabled', $meta, true);
        }
        return $meta;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function modifyData(array $data)
    {
        return $this->modifier->modifyData($data);
    }
}
