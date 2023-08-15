<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\ScheduleDesignUpdate as CatalogScheduleDesignUpdate;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class ScheduleDesignUpdate implements ModifierInterface
{
    /**
     * @var CatalogScheduleDesignUpdate
     */
    protected $modifier;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     * @param CatalogScheduleDesignUpdate $modifier
     */
    public function __construct(
        ArrayManager $arrayManager,
        CatalogScheduleDesignUpdate $modifier
    ) {
        $this->arrayManager = $arrayManager;
        $this->modifier = $modifier;
    }

    /**
     * Move 'custom_design' component to 'design' > 'schedule-design-update'
     *
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->modifier->modifyMeta($meta);
        $customDesignPath = $this->arrayManager->findPath('custom_design', $meta);
        if ($customDesignPath === null)
        {
            return $meta;
        }
        $containerPath = $this->arrayManager->slicePath($customDesignPath, 0, 3);

        $meta = $this->arrayManager->merge(
            $containerPath . CatalogScheduleDesignUpdate::META_CONFIG_PATH,
            $meta,
            [
                'sortOrder' => 0
            ]
        );
        $meta = $this->arrayManager->merge(
            $customDesignPath . CatalogScheduleDesignUpdate::META_CONFIG_PATH,
            $meta,
            [
                'label' => 'Theme',
            ]
        );
        if (!$this->arrayManager->get('design', $meta)) {
            $designTab = $this->arrayManager->get('schedule-design-update/arguments', $meta);
            $designTab = $this->arrayManager->set('data/config/label', $designTab, (string)__('Design'));
            $meta = $this->arrayManager->set('design/arguments', $meta, $designTab);
        }
        $customDesignTab = $this->arrayManager->get($containerPath, $meta);
        $meta = $this->arrayManager->set('design/children/schedule-design-update', $meta, $customDesignTab);
        $meta = $this->arrayManager->remove($customDesignPath, $meta);
        $meta = $this->arrayManager->remove('schedule-design-update', $meta);
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $this->modifier->modifyData($data);
    }
}
