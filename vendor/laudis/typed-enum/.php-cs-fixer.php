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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<'EOF'
This file is part of the Laudis TypedEnum library

(c) Laudis <https://laudis.tech>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = Finder::create()
    ->in(__DIR__.'/src/')
    ->in(__DIR__.'/tests/')
;

return (new Config())
    ->setRules([
        '@Symfony' => true,

        'array_syntax' => ['syntax' => 'short'],
        'header_comment' => ['header' => $header],
        'linebreak_after_opening_tag' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
;
