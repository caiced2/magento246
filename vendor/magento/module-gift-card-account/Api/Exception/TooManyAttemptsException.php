<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Api\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Too many attempts to use gift card codes were made.
 *
 * @api
 */
class TooManyAttemptsException extends LocalizedException
{

}
