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

namespace Laudis\TypedEnum\Tests\Implementation;

use Laudis\TypedEnum\TypedEnum;

/**
 * @method static NonScalarImplementation NULL()
 *
 * @extends TypedEnum<null>
 */
final class NonScalarImplementation extends TypedEnum
{
    private const NULL = null;
}
