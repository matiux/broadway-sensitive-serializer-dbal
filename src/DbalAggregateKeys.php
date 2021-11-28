<?php

declare(strict_types=1);

namespace Matiux\Broadway\SensitiveSerializer\Dbal;

use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKey;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKeys;
use Ramsey\Uuid\UuidInterface;

class DbalAggregateKeys implements AggregateKeys
{
    public function __construct()
    {
    }

    public function add(AggregateKey $aggregateKey): void
    {
        // TODO: Implement add() method.
    }

    public function withAggregateId(UuidInterface $aggregateId): ?AggregateKey
    {
        // TODO: Implement withAggregateId() method.
    }

    public function update(AggregateKey $aggregateKey): void
    {
        // TODO: Implement update() method.
    }
}
