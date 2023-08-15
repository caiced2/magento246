<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Model;

/**
 * Service for ordering related, up-sell, cross-sell products
 */
class Rotation
{
    /**
     * Reorder a list of product ids by defined rotation mode
     *
     * @param array $list
     * @param int $rotationMode
     * @param int|null $limit
     * @return array
     */
    public function reorder(array $list, int $rotationMode, ?int $limit = null): array
    {
        if ($rotationMode == Rule::ROTATION_SHUFFLE) {
            $list = $this->random($list);
        } elseif ($rotationMode == \Magento\TargetRule\Model\Source\Rotation::ROTATION_WEIGHTED_RANDOM) {
            if ($limit > 0) {
                $list = $this->weightedRandom($list);
                $list = array_slice($list, 0, $limit, true);
            } else {
                $list = $this->random($list);
            }
        } else {
            ksort($list, SORT_NUMERIC);
        }
        $listGroupByPriority = [];
        foreach ($list as $id => $priority) {
            $listGroupByPriority[$priority][] = $id;
        }
        ksort($listGroupByPriority, SORT_NUMERIC);
        $orderedList = [];
        foreach ($listGroupByPriority as $byPriority) {
            array_push($orderedList, ...$byPriority);
        }
        $orderedList = array_replace(array_flip($orderedList), $list);
        return $limit < 0 ? [] : array_slice($orderedList, 0, $limit, true);
    }

    /**
     * Order associative array by key in random order
     *
     * @param array $list
     * @return array
     */
    private function random(array $list): array
    {
        $keys = array_keys($list);
        shuffle($keys);
        $random = [];
        foreach ($keys as $key) {
            $random[$key] = $list[$key];
        }
        return $random;
    }

    /**
     * Order associative array by key in weighted random order where values are respective priority
     *
     * @param array $list
     * @return array
     */
    private function weightedRandom(array $list): array
    {
        $weights = [];
        $priorities = array_values(array_unique($list));
        rsort($priorities, SORT_NUMERIC);
        $weight = 2;
        foreach ($priorities as $priority) {
            $weights[$priority] = log($weight);
            $weight += 2;
        }
        $normalizedList = [];
        foreach ($list as $key => $priority) {
            $normalizedList[$key] = $weights[$priority];
        }
        $random = [];
        foreach ($normalizedList as $key => $weight) {
            $random[$key] = pow(random_int(1, 99) / 100, 1 / $weight);
        }
        arsort($random, SORT_NUMERIC);
        return array_replace($random, $list);
    }
}
