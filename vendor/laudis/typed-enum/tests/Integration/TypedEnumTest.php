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

namespace Laudis\TypedEnum\Tests\Integration;

use Laudis\TypedEnum\Errors\NonExistingEnumerationError;
use Laudis\TypedEnum\Tests\Implementation\SecondTypedEnum;
use Laudis\TypedEnum\Tests\Implementation\TypedEnumFilled;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @psalm-suppress PossiblyUndefinedIntArrayOffset
 */
final class TypedEnumTest extends TestCase
{
    public function testCallInt(): void
    {
        self::assertSame(1, TypedEnumFilled::TEST_NUMERIC()->getValue());
    }

    public function testDifferentInt(): void
    {
        self::assertSame('abc', TypedEnumFilled::TEST_NO_SAME_VALUE()->getValue());
    }

    public function testCallString(): void
    {
        self::assertSame('test', TypedEnumFilled::TEST()->getValue());
    }

    public function testCallSame(): void
    {
        self::assertSame('same', TypedEnumFilled::TEST_SAME_ONE()->getValue());
        self::assertSame('same', TypedEnumFilled::TEST_SAME_TWO()->getValue());
    }

    public function testResolveInt(): void
    {
        self::assertSame(TypedEnumFilled::TEST_NUMERIC(), TypedEnumFilled::resolve(1)[0]);
        self::assertCount(1, TypedEnumFilled::resolve(1));
    }

    public function testResolveDifferent(): void
    {
        self::assertSame(TypedEnumFilled::TEST_NO_SAME_VALUE(), TypedEnumFilled::resolve('abc')[0]);
        self::assertCount(1, TypedEnumFilled::resolve('abc'));
    }

    public function testResolveString(): void
    {
        self::assertSame(TypedEnumFilled::TEST(), TypedEnumFilled::resolve('test')[0]);
        self::assertCount(1, TypedEnumFilled::resolve('test'));
    }

    public function testResolveSame(): void
    {
        self::assertSame(TypedEnumFilled::TEST_SAME_ONE(), TypedEnumFilled::resolve('same')[0]);
        self::assertSame(TypedEnumFilled::TEST_SAME_TWO(), TypedEnumFilled::resolve('same')[1]);
        self::assertCount(2, TypedEnumFilled::resolve('same'));
    }

    public function testGetAllInstances(): void
    {
        self::assertEquals(
            [
                'TEST' => TypedEnumFilled::TEST(),
                'TEST_NO_SAME_VALUE' => TypedEnumFilled::TEST_NO_SAME_VALUE(),
                'TEST_NUMERIC' => TypedEnumFilled::TEST_NUMERIC(),
                'TEST_SAME_ONE' => TypedEnumFilled::TEST_SAME_ONE(),
                'TEST_SAME_TWO' => TypedEnumFilled::TEST_SAME_TWO(),
            ],
            TypedEnumFilled::getAllInstances()
        );
    }

    public function testGetAllInstancesReversed(): void
    {
        self::assertEquals(
            [
                'TEST' => TypedEnumFilled::TEST(),
                'TEST_NO_SAME_VALUE' => TypedEnumFilled::TEST_NO_SAME_VALUE(),
                'TEST_NUMERIC' => TypedEnumFilled::TEST_NUMERIC(),
                'TEST_SAME_ONE' => TypedEnumFilled::TEST_SAME_ONE(),
                'TEST_SAME_TWO' => TypedEnumFilled::TEST_SAME_TWO(),
            ],
            TypedEnumFilled::getAllInstances()
        );
    }

    public function testEqual(): void
    {
        self::assertSame(TypedEnumFilled::TEST(), TypedEnumFilled::TEST());
    }

    public function testAssertNotSameInExtendedVersion(): void
    {
        self::assertNotSame(SecondTypedEnum::TEST(), TypedEnumFilled::TEST());
        self::assertNotSame(SecondTypedEnum::TEST_NO_SAME_VALUE(), TypedEnumFilled::TEST_NO_SAME_VALUE());
        self::assertNotSame(SecondTypedEnum::TEST_NUMERIC(), TypedEnumFilled::TEST_NUMERIC());
        self::assertNotSame(SecondTypedEnum::TEST_SAME_ONE(), TypedEnumFilled::TEST_SAME_ONE());
        self::assertNotSame(SecondTypedEnum::TEST_SAME_TWO(), TypedEnumFilled::TEST_SAME_TWO());
    }

    public function testNonEnumeration(): void
    {
        $this->expectException(NonExistingEnumerationError::class);
        /** @psalm-suppress InvalidArgument */
        SecondTypedEnum::ABC();
    }

    public function testResolveNull(): void
    {
        self::assertEmpty(SecondTypedEnum::resolve('fdjker;qahg'));
    }
}
