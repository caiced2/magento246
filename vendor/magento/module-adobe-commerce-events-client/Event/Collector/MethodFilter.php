<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Filters methods from services.
 * Example of list of excluded methods:
 * [
 *    'delete',
 *    '/^get.'*'/'
 * ]
 */
class MethodFilter
{
    private const DEFAULT_EXCLUDES = [
        '/^(is|_|set|get|unset|validate|has|unserialize|serialize|count|load|' .
        'reindex|sync|filter|prepare|find|walk|clear).*/i',
        '/.*(load|cookie)$/i',
        'parse',
        'clean',
        'rollBack',
        'afterDelete',
        'afterSave',
        'commit',
        'beginTransaction',
        'addCommitCallback',
        'beforeSave',
        '_beforeSave',
        'beforeDelete',
        'addUniqueField',
        'resetUniqueField',
        'insertFromSelect',
        'insertFromTable',
    ];

    /**
     * @var string[]
     */
    private array $excludedMethods;

    /**
     * @param string[] $excludedMethods
     * @throws InvalidArgumentException
     */
    public function __construct(array $excludedMethods = self::DEFAULT_EXCLUDES)
    {
        foreach ($excludedMethods as $filter) {
            if (!is_string($filter)) {
                throw new InvalidArgumentException(__('All elements in excludedMethods must be type of the string.'));
            }
        }

        $this->excludedMethods = $excludedMethods;
    }

    /**
     * Checks if method should be excluded.
     *
     * @param string $methodName
     * @return bool
     */
    public function isExcluded(string $methodName): bool
    {
        foreach ($this->excludedMethods as $filter) {
            // phpcs:ignore Generic.PHP.NoSilencedErrors
            if ($methodName === $filter || @preg_match($filter, $methodName) === 1) {
                return true;
            }
        }

        return false;
    }
}
