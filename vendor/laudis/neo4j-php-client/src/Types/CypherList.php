<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\Types;

use Laudis\Neo4j\Exception\RuntimeTypeException;
use Laudis\Neo4j\TypeCaster;

/**
 * An immutable ordered sequence of items.
 *
 * @template TValue
 *
 * @extends ArrayList<TValue>
 *
 * @psalm-immutable
 */
class CypherList extends ArrayList
{
    /**
     * @return CypherMap<mixed>
     */
    public function getAsCypherMap(int $key): CypherMap
    {
        $value = $this->get($key);
        $tbr = TypeCaster::toCypherMap($value);
        if ($tbr === null) {
            throw new RuntimeTypeException($value, CypherMap::class);
        }

        return $tbr;
    }

    /**
     * @return CypherList<mixed>
     */
    public function getAsCypherList(int $key): CypherList
    {
        $value = $this->get($key);
        $tbr = TypeCaster::toCypherList($value);
        if ($tbr === null) {
            throw new RuntimeTypeException($value, CypherList::class);
        }

        return $tbr;
    }

    public function getAsDate(int $key): Date
    {
        return $this->getAsObject($key, Date::class);
    }

    public function getAsDateTime(int $key): DateTime
    {
        return $this->getAsObject($key, DateTime::class);
    }

    public function getAsDuration(int $key): Duration
    {
        return $this->getAsObject($key, Duration::class);
    }

    public function getAsLocalDateTime(int $key): LocalDateTime
    {
        return $this->getAsObject($key, LocalDateTime::class);
    }

    public function getAsLocalTime(int $key): LocalTime
    {
        return $this->getAsObject($key, LocalTime::class);
    }

    public function getAsTime(int $key): Time
    {
        return $this->getAsObject($key, Time::class);
    }

    public function getAsNode(int $key): Node
    {
        return $this->getAsObject($key, Node::class);
    }

    public function getAsRelationship(int $key): Relationship
    {
        return $this->getAsObject($key, Relationship::class);
    }

    public function getAsPath(int $key): Path
    {
        return $this->getAsObject($key, Path::class);
    }

    public function getAsCartesian3DPoint(int $key): Cartesian3DPoint
    {
        return $this->getAsObject($key, Cartesian3DPoint::class);
    }

    public function getAsCartesianPoint(int $key): CartesianPoint
    {
        return $this->getAsObject($key, CartesianPoint::class);
    }

    public function getAsWGS84Point(int $key): WGS84Point
    {
        return $this->getAsObject($key, WGS84Point::class);
    }

    public function getAsWGS843DPoint(int $key): WGS843DPoint
    {
        return $this->getAsObject($key, WGS843DPoint::class);
    }

    /**
     * @template Value
     *
     * @param iterable<Value> $iterable
     *
     * @return self<Value>
     *
     * @pure
     */
    public static function fromIterable(iterable $iterable): CypherList
    {
        return new self($iterable);
    }
}
