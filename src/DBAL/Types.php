<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\DBAL;

use Doctrine\DBAL\Types\Types as DBALTypes;
use Drewlabs\CodeGenerator\Types\PHPTypes;

final class Types
{
    /** @var string */
    private $t;

    /**
     * types class constructor.
     *
     * @return void
     */
    public function __construct(string $t)
    {
        $this->t = $t;
    }

    /**
     * types class factory constructor.
     *
     * @return static
     */
    public static function new(string $t)
    {
        return new static($t);
    }

    /**
     * returns php type representation of the DBAL type.
     */
    public function toPHPType(): string
    {
        $types = [
            DBALTypes::ASCII_STRING => PHPTypes::STRING,
            DBALTypes::BIGINT => PHPTypes::INT,
            DBALTypes::BINARY => PHPTypes::STRING,
            DBALTypes::BLOB => PHPTypes::STRING,
            DBALTypes::BOOLEAN => PHPTypes::BOOLEAN,
            DBALTypes::DATE_MUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DATE_IMMUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DATEINTERVAL => PHPTypes::INT,
            DBALTypes::DATETIME_MUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DATETIME_IMMUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DATETIMETZ_MUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DATETIMETZ_IMMUTABLE => PHPTypes::STRING.'|\\'.\DateTimeInterface::class,
            DBALTypes::DECIMAL => PHPTypes::FLOAT,
            DBALTypes::ENUM => PHPTypes::STRING,
            DBALTypes::FLOAT => PHPTypes::FLOAT,
            DBALTypes::GUID => PHPTypes::STRING,
            DBALTypes::INTEGER => PHPTypes::INT,
            DBALTypes::JSON => PHPTypes::STRING,
            DBALTypes::SIMPLE_ARRAY => PHPTypes::LIST,
            DBALTypes::SMALLFLOAT => PHPTypes::FLOAT,
            DBALTypes::SMALLINT => PHPTypes::INT,
            DBALTypes::STRING => PHPTypes::STRING,
            DBALTypes::TEXT => PHPTypes::STRING,
            DBALTypes::TIME_MUTABLE => PHPTypes::INT,
            DBALTypes::TIME_IMMUTABLE => PHPTypes::INT,
        ];

        return $types[$this->t] ?? 'mixed';
    }
}
