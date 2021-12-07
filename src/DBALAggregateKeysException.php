<?php

declare(strict_types=1);

namespace Matiux\Broadway\SensitiveSerializer\Dbal;

use Exception;
use RuntimeException;

class DBALAggregateKeysException extends RuntimeException
{
    public static function create(Exception $exception): DBALAggregateKeysException
    {
        return new DBALAggregateKeysException('', 0, $exception);
    }
}
