<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for overwriting existing target rule with wrong special type
 */
class OverwriteWrongTargetRule implements DataPatchInterface
{
    /**
     * Special price class name
     */
    private const SPECIAL_PRICE_CLASS = \Magento\TargetRule\Model\Actions\Condition\Product\Special\Price::class;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Json $jsonSerializer
     * @param State $state
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        Json $jsonSerializer,
        State $state
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->state = $state;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        // Emulate area for rules migration
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'overwriteTargetRule']
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            ConvertSerializedDataToJson::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Overwrite wrong target rule.
     *
     * @return void
     */
    public function overwriteTargetRule()
    {
        /** @var \Magento\TargetRule\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->ruleCollectionFactory->create();
        $collection->load();
        foreach ($collection->getItems() as $item) {
            $actions = $this->jsonSerializer->unserialize($item->getData('actions_serialized'));
            if (array_key_exists('conditions', $actions) && !empty($actions['conditions'])) {
                $conditions = $actions['conditions'];
                foreach ($conditions as $key => $condition) {
                    if ($condition['type'] == self::SPECIAL_PRICE_CLASS) {
                        $actions['conditions'][$key]['attribute'] = 'price';
                    }
                }
            }
            $newAction = $this->jsonSerializer->serialize($actions);
            $item->setData('actions_serialized', $newAction);
            $item->setData('action_select', null);
        }
        $collection->save();
    }
}
