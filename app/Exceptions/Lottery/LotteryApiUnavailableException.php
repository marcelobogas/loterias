<?php

namespace App\Exceptions\Lottery;

use RuntimeException;

/**
 * The Caixa API could not be reached (timeout, connection failure, DNS, etc).
 */
class LotteryApiUnavailableException extends RuntimeException {}
