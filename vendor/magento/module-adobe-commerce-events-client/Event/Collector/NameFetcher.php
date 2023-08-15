<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\Exception\LocalizedException;
use SplFileInfo;

/**
 * Used for fetching fully qualified class or interface names from the file
 */
class NameFetcher
{
    /**
     * Simple way to get class or interface name from Class.
     *
     * Can be improved by parsing file with php token.
     *
     * @param SplFileInfo $fileInfo
     * @param string|null $fileContent
     * @return string
     * @throws LocalizedException
     */
    public function getNameFromFile(SplFileInfo $fileInfo, string $fileContent = null): string
    {
        if (empty($fileContent)) {
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            $fileContent = file_get_contents($fileInfo->getPathname());
        }

        preg_match('/^namespace\s+(?<namespace>.*).*;/im', $fileContent, $matches);

        if (empty($matches['namespace'])) {
            throw new LocalizedException(__('Could not fetch namespace from the file: %1', $fileInfo->getPathname()));
        }
        $namespace = $matches['namespace'];

        $patterns = [
            '/^(abstract\s)?class\s+(?<class>\w*)/im' => 'class',
            '/^interface\s+(?<interface>\w*)/im' => 'interface',
        ];

        foreach ($patterns as $pattern => $match) {
            preg_match($pattern, $fileContent, $matches);

            if (!empty($matches[$match])) {
                return $namespace . '\\' . $matches[$match];
            }
        }

        throw new LocalizedException(
            __('Could not fetch Class or Interface name from the file: %1', $fileInfo->getPathname())
        );
    }
}
