<?php

declare(strict_types=1);

namespace Tests\Matiux\Broadway\SensitiveSerializer\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Exception;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKey;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKeys;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Exception\AggregateKeyNotFoundException;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Exception\DuplicatedAggregateKeyException;
use Matiux\Broadway\SensitiveSerializer\Dbal\DBALAggregateKeys;
use Matiux\Broadway\SensitiveSerializer\Dbal\DBALAggregateKeysException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DBALAggregateKeysTest extends TestCase
{
    private AggregateKeys  $aggregateKeys;

    protected function setUp(): void
    {
        //?serverVersion=mariadb-10.3.22
        //$connection = DriverManager::getConnection(['driver' => 'pdo_mysql', 'url' => 'mysql://root:root@servicedb:3306/aggregate_keys']);
        //$connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => '../aggregate_keys.db']);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite',  'memory' => true]);
        $schemaManager = $connection->createSchemaManager();
        $schema = $schemaManager->createSchema();

        $this->aggregateKeys = new DBALAggregateKeys(
            $connection,
            'aggregate_keys',
            false
        );

        $table = $this->aggregateKeys->configureSchema($schema);
        self::assertNotNull($table);
        $schemaManager->createTable($table);
    }

    /**
     * @test
     */
    public function it_allows_no_binary_uuid_converter_provided_when_not_using_binary(): void
    {
        new DBALAggregateKeys(
            Mockery::mock(Connection::class),
            'aggregate_keys',
            false,
            null
        );
    }

    /**
     * @test
     */
    public function it_throws_when_an_error_occurs_during_adding(): void
    {
        self::expectException(DBALAggregateKeysException::class);

        $connection = Mockery::mock(Connection::class)
            ->shouldReceive('insert')->andThrow(new Exception())
            ->getMock();

        $aggregateKeys = new DBALAggregateKeys(
            $connection,
            'aggregate_keys',
            false
        );

        $aggregateKey = AggregateKey::create(Uuid::uuid4(), 'eNCr1p73dS3kr3tk31');

        $aggregateKeys->add($aggregateKey);
    }

    /**
     * @test
     */
    public function it_throws_when_an_id_is_duplicated(): void
    {
        $id = Uuid::uuid4();

        self::expectException(DuplicatedAggregateKeyException::class);
        self::expectExceptionMessage(sprintf('Duplicated aggregateKey with id %s', $id->toString()));

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');

        $this->aggregateKeys->add($aggregateKey);
        $this->aggregateKeys->add($aggregateKey);
    }

    /**
     * @test
     */
    public function it_returns_null_when_aggregate_key_does_not_exist(): void
    {
        $id = Uuid::uuid4();

        $aggregateKey = $this->aggregateKeys->withAggregateId($id);

        self::assertNull($aggregateKey);
    }

    /**
     * @test
     */
    public function should_find_not_canceled_aggregate_key(): void
    {
        $id = Uuid::uuid4();

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');

        $this->aggregateKeys->add($aggregateKey);

        $aggregateKey = $this->aggregateKeys->withAggregateId($id);

        self::assertNotNull($aggregateKey);
        self::assertNull($aggregateKey->cancellationDate());
        self::assertSame('eNCr1p73dS3kr3tk31', (string) $aggregateKey);
        self::assertTrue($aggregateKey->aggregateId()->equals($id));
    }

    /**
     * @test
     */
    public function should_find_canceled_aggregate_key(): void
    {
        $id = Uuid::uuid4();

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');
        $aggregateKey->delete();

        $this->aggregateKeys->add($aggregateKey);

        $aggregateKey = $this->aggregateKeys->withAggregateId($id);

        self::assertNotNull($aggregateKey);
        self::assertNotNull($aggregateKey->cancellationDate());
        self::assertSame('', (string) $aggregateKey);
        self::assertTrue($aggregateKey->aggregateId()->equals($id));
    }

    /**
     * @test
     */
    public function it_throws_when_update_not_existing_aggregate_key(): void
    {
        $id = Uuid::uuid4();

        self::expectException(AggregateKeyNotFoundException::class);
        self::expectExceptionMessage(sprintf('AggregateKey not found for aggregate %s', $id->toString()));

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');

        $this->aggregateKeys->update($aggregateKey);
    }

    /**
     * @test
     */
    public function should_update_aggregate_key(): void
    {
        $id = Uuid::uuid4();

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');

        $this->aggregateKeys->add($aggregateKey);

        $persistedAggregateKey = $this->aggregateKeys->withAggregateId($id);

        self::assertNotNull($persistedAggregateKey);

        $cancellationDate = $persistedAggregateKey->cancellationDate();
        self::assertNull($cancellationDate);

        self::assertTrue($persistedAggregateKey->exists());
        self::assertSame('eNCr1p73dS3kr3tk31', (string) $persistedAggregateKey);

        $persistedAggregateKey->delete();
        $this->aggregateKeys->update($aggregateKey);
        $cancellationDate = $persistedAggregateKey->cancellationDate();

        self::assertNotNull($cancellationDate);
        self::assertFalse($persistedAggregateKey->exists());
        self::assertSame('', (string) $persistedAggregateKey);
    }
}
