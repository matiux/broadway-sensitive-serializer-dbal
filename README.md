broadway-sensitive-serializer-dbal
===

![check dependencies](https://github.com/matiux/broadway-sensitive-serializer-dbal/actions/workflows/check-dependencies.yml/badge.svg)
![test](https://github.com/matiux/broadway-sensitive-serializer-dbal/actions/workflows/tests.yml/badge.svg)
[![codecov](https://codecov.io/gh/matiux/broadway-sensitive-serializer-dbal/branch/master/graph/badge.svg)](https://codecov.io/gh/matiux/broadway-sensitive-serializer-dbal)
[![type coverage](https://shepherd.dev/github/matiux/broadway-sensitive-serializer-dbal/coverage.svg)](https://shepherd.dev/github/matiux/broadway-sensitive-serializer-dbal)
[![psalm level](https://shepherd.dev/github/matiux/broadway-sensitive-serializer-dbal/level.svg)](https://shepherd.dev/github/matiux/broadway-sensitive-serializer-dbal)
![security analysis status](https://github.com/matiux/broadway-sensitive-serializer-dbal/actions/workflows/security-analysis.yml/badge.svg)
![coding standards status](https://github.com/matiux/broadway-sensitive-serializer-dbal/actions/workflows/coding-standards.yml/badge.svg)

Broadway [sensitive serializer](https://github.com/matiux/broadway-sensitive-serializer) dbal implementation
using [doctrine/dbal](https://github.com/doctrine/dbal).

Read the [wiki](https://github.com/matiux/broadway-sensitive-serializer/wiki) for more information.

## Setup for development

```shell
git clone https://github.com/matiux/broadway-sensitive-serializer-dbal.git && cd broadway-sensitive-serializer-dbal
cp docker/docker-compose.override.dist.yml docker/docker-compose.override.yml
rm -rf .git/hooks && ln -s ../scripts/git-hooks .git/hooks
```
### Install dependencies to run test
```shell
./dc up -d
./dc enter
composer install
```
### Run test
```shell
./dc up -d
./dc enter
project phpunit
```
## Symfony container registration

```yaml
services:
  broadway_sensitive_serializer.aggregate_keys.dbal:
    class: Matiux\Broadway\SensitiveSerializer\Dbal\DBALAggregateKeys
    arguments:
      $connection: "@doctrine.dbal.default_connection"
      $tableName: "aggregate_keys"
      $useBinary: false
      $binaryUuidConverter: "@broadway.uuid.converter"
```