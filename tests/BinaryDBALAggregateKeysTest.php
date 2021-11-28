<?php

declare(strict_types=1);

namespace Tests\Matiux\Broadway\SensitiveSerializer\Dbal;

use Broadway\EventStore\Exception\InvalidIdentifierException;
use Broadway\UuidGenerator\Converter\BinaryUuidConverter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use LogicException;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKey;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKeys;
use Matiux\Broadway\SensitiveSerializer\Dbal\DBALAggregateKeys;
use Mockery;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class BinaryDBALAggregateKeysTest extends TestCase
{
    private AggregateKeys  $aggregateKeys;
    private Table $table;

    protected function setUp(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite',  'memory' => true]);
        $schemaManager = $connection->createSchemaManager();
        $schema = $schemaManager->createSchema();

        $this->aggregateKeys = new DBALAggregateKeys(
            $connection,
            'aggregate_keys',
            true,
            new BinaryUuidConverter()
        );

        $table = $this->aggregateKeys->configureSchema($schema);
        self::assertNotNull($table);
        $this->table = $table;
        $schemaManager->createTable($this->table);
    }

    /**
     * @test
     */
    public function table_should_contain_binary_uuid_column(): void
    {
        $uuidColumn = $this->table->getColumn('aggregate_uuid');

        $this->assertEquals(16, $uuidColumn->getLength());
        $this->assertEquals(Type::getType(Types::BINARY), $uuidColumn->getType());
        $this->assertTrue($uuidColumn->getFixed());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_an_id_is_no_uuid_in_binary_mode(): void
    {
        $id = Mockery::mock(Uuid::class)->shouldReceive('toString')->andReturn('bleeh')->getMock();

        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionMessage('Only valid UUIDs are allowed to by used with the binary storage mode.');

        $aggregateKey = AggregateKey::create($id, 'eNCr1p73dS3kr3tk31');

        $this->aggregateKeys->add($aggregateKey);
    }

    /**
     * @test
     */
    public function it_throws_when_no_binary_uuid_converter_provided_when_using_binary(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('binary UUID converter is required when using binary');

        new DBALAggregateKeys(
            Mockery::mock(Connection::class),
            'aggregate_keys',
            true,
            null
        );
    }
}
