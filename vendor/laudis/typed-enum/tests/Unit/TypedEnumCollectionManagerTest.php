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

namespace Laudis\TypedEnum\Tests\Unit;

use Laudis\TypedEnum\Tests\Implementation\NonScalarImplementation;
use Laudis\TypedEnum\Tests\Implementation\SecondTypedEnum;
use Laudis\TypedEnum\Tests\Implementation\TypedEnumFilled;
use Laudis\TypedEnum\TypedEnumCollectionManager;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @psalm-suppress PossiblyUndefinedStringArrayOffset
 */
final class TypedEnumCollectionManagerTest extends TestCase
{
    private TypedEnumCollectionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new TypedEnumCollectionManager();
    }

    public function testEquals(): void
    {
        $values = $this->manager->get(NonScalarImplementation::class);
        self::assertEquals(NonScalarImplementation::NULL(), $values['NULL']);
    }

    public function testMultiple(): void
    {
        /** @psalm-suppress InvalidArgument */
        $values = $this->manager->get(SecondTypedEnum::class);
        self::assertEquals(SecondTypedEnum::TEST(), $values['TEST']);
        self::assertEquals(SecondTypedEnum::TEST_NO_SAME_VALUE(), $values['TEST_NO_SAME_VALUE']);
        self::assertEquals(SecondTypedEnum::TEST_NUMERIC(), $values['TEST_NUMERIC']);
        self::assertEquals(SecondTypedEnum::TEST_SAME_ONE(), $values['TEST_SAME_ONE']);
        self::assertEquals(SecondTypedEnum::TEST_SAME_TWO(), $values['TEST_SAME_TWO']);
    }

    public function testSame(): void
    {
        $values = $this->manager->get(NonScalarImplementation::class);
        self::assertSame($this->manager->get(NonScalarImplementation::class)['NULL'] ?? null, $values['NULL']);
    }

    public function testEqualsButNotSame(): void
    {
        $values = $this->manager->get(TypedEnumFilled::class);
        $otherValues = (new TypedEnumCollectionManager())->get(TypedEnumFilled::class);
        self::assertEquals($values, $otherValues);
        self::assertNotSame($values, $otherValues);
    }
}
