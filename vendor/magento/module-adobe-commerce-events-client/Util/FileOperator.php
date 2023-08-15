<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Util;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Helper class for filesystem operations
 */
class FileOperator
{
    /**
     * Returns recursive directory iterator for given path with given pattern for files to find
     *
     * @param string $dir
     * @param array $regexPatterns List of regex file pattern to find
     * @return RegexIterator
     */
    public function getRecursiveFileIterator(
        string $dir,
        array $regexPatterns
    ): RegexIterator {
        $dirIterator = new RecursiveDirectoryIterator($dir);
        $recursiveDirIterator = new RecursiveIteratorIterator($dirIterator);
        foreach ($regexPatterns as $pattern) {
            if (!empty($pattern)) {
                $recursiveDirIterator = new RegexIterator($recursiveDirIterator, $pattern, RegexIterator::MATCH);
            }
        }

        return $recursiveDirIterator;
    }

    /**
     * Factory method for creating DirectoryIterator object
     *
     * @param string $dir
     * @return DirectoryIterator
     */
    public function getDirectoryIterator(string $dir): DirectoryIterator
    {
        return new DirectoryIterator($dir);
    }
}
