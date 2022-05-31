<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis TypedEnum library
 *
 * (c) Laudis <https://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\TypedEnum;

use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
final class TypedEnumCollectionManager
{
    /** @var array<class-string<TypedEnum>, array<string, TypedEnum>> */
    private array $mappings = [];

    /**
     * @template T of TypedEnum
     *
     * @param class-string<T> $class
     *
     * @throws ReflectionException
     *
     * @return array<string, T>
     */
    public function get(string $class): array
    {
        /** @var array<string, T>|null $tbr */
        $tbr = $this->mappings[$class] ?? null;

        if ($tbr === null) {
            $reflector = new ReflectionClass($class);

            /** @var array<string, T> $tbr */
            $tbr = [];
            foreach ($reflector->getReflectionConstants() as $constant) {
                $tbr[$constant->getName()] = new $class($constant->getValue());
            }

            $this->mappings[$reflector->getName()] = $tbr;
        }

        return $tbr;
    }
}
