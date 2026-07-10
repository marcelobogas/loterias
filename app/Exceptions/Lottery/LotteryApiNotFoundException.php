<?php

namespace App\Exceptions\Lottery;

use RuntimeException;

/**
 * The requested contest does not exist yet (not drawn) or the API path is invalid.
 */
class LotteryApiNotFoundException extends RuntimeException {}
