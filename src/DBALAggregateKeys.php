<?php

declare(strict_types=1);

namespace Matiux\Broadway\SensitiveSerializer\Dbal;

use Broadway\EventStore\Exception\InvalidIdentifierException;
use Broadway\UuidGenerator\Converter\BinaryUuidConverterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Statement;
use Exception;
use LogicException;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKey;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Aggregate\AggregateKeys;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Exception\AggregateKeyNotFoundException;
use Matiux\Broadway\SensitiveSerializer\DataManager\Domain\Exception\DuplicatedAggregateKeyException;
use Ramsey\Uuid\UuidInterface;

/**
 * @psalm-import-type SerializedAKey from AggregateKey
 */
class DBALAggregateKeys implements AggregateKeys
{
    private Connection $connection;
    private string $tableName;
    private bool $useBinary;
    private ?BinaryUuidConverterInterface $binaryUuidConverter;

    public function __construct(
        Connection $connection,
        string $tableName,
        bool $useBinary,
        BinaryUuidConverterInterface $binaryUuidConverter = null
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->useBinary = $useBinary;
        $this->binaryUuidConverter = $binaryUuidConverter;

        if ($this->useBinary && null === $binaryUuidConverter) {
            throw new LogicException('binary UUID converter is required when using binary');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws DBALAggregateKeysException
     */
    public function add(AggregateKey $aggregateKey): void
    {
        try {
            $this->insertAggregateKey($aggregateKey);
        } catch (UniqueConstraintViolationException $exception) {
            throw DuplicatedAggregateKeyException::create($aggregateKey->aggregateId(), $exception);
        } catch (InvalidIdentifierException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw DBALAggregateKeysException::create($exception);
        }
    }

    /**
     * @param AggregateKey $aggregateKey
     *
     * @throws Exception
     */
    private function insertAggregateKey(AggregateKey $aggregateKey): void
    {
        $data = $aggregateKey->serialize();
        $data['aggregate_uuid'] = $this->convertIdentifierToStorageValue((string) $aggregateKey->aggregateId());

        $this->connection->insert($this->tableName, $data);
    }

    private function convertIdentifierToStorageValue(string $id): string
    {
        if ($this->useBinary && $this->binaryUuidConverter) {
            try {
                return $this->binaryUuidConverter::fromString($id);
            } catch (\Exception $e) {
                throw new InvalidIdentifierException('Only valid UUIDs are allowed to by used with the binary storage mode.');
            }
        }

        return $id;
    }

    /**
     * @param UuidInterface $aggregateId
     *
     * @throws Exception
     *
     * @return null|AggregateKey
     */
    public function withAggregateId(UuidInterface $aggregateId): ?AggregateKey
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue(1, $this->convertIdentifierToStorageValue((string) $aggregateId));
        $result = $statement->executeQuery();

        /** @var false|SerializedAKey $row */
        $row = $result->fetchAssociative();

        if (!$row) {
            return null;
        }

        return AggregateKey::deserialize($row);
    }

    /**
     * @psalm-suppress all
     *
     * @throws Exception
     *
     * @return Statement
     */
    private function prepareLoadStatement(): Statement
    {
        $query = sprintf(
            'SELECT aggregate_uuid, encrypted_key, cancellation_date
                FROM %s
                WHERE aggregate_uuid = ?',
            $this->tableName
        );

        return $this->connection->prepare($query);
    }

    /**
     * @param AggregateKey $aggregateKey
     *
     * @throws AggregateKeyNotFoundException|Exception
     */
    public function update(AggregateKey $aggregateKey): void
    {
        if (!$this->withAggregateId($aggregateKey->aggregateId())) {
            throw AggregateKeyNotFoundException::create($aggregateKey->aggregateId());
        }

        $data = $aggregateKey->serialize();
        unset($data['aggregate_uuid']);

        $this->connection->update($this->tableName, $data, ['aggregate_uuid' => (string) $aggregateKey->aggregateId()]);
    }

    public function configureSchema(Schema $schema): ?Table
    {
        if ($schema->hasTable($this->tableName)) {
            return null;
        }

        return $this->configureTable($schema);
    }

    /**
     * @param null|Schema $schema
     *
     * @throws SchemaException
     *
     * @return Table
     */
    public function configureTable(Schema $schema = null): Table
    {
        $schema = $schema ?: new Schema();

        $uuidColumnDefinition = [
            'type' => 'guid',
            'params' => [
                'length' => 36,
            ],
        ];

        if ($this->useBinary) {
            $uuidColumnDefinition['type'] = 'binary';
            $uuidColumnDefinition['params'] = [
                'length' => 16,
                'fixed' => true,
            ];
        }

        $table = $schema->createTable($this->tableName);

        $table->addColumn('aggregate_uuid', $uuidColumnDefinition['type'], $uuidColumnDefinition['params']);
        $table->addColumn('encrypted_key', 'text');
        $table->addColumn('cancellation_date', 'string', ['length' => 32])->setNotnull(false);

        $table->setPrimaryKey(['aggregate_uuid']);

        return $table;
    }
}
