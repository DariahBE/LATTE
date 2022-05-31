<?php

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\Bolt;

use function count;
use function explode;
use const FILTER_VALIDATE_IP;
use function filter_var;
use Laudis\Neo4j\Databags\DriverConfiguration;
use Laudis\Neo4j\Enum\SslMode;
use Laudis\Neo4j\Neo4j\RoutingTable;
use Psr\Http\Message\UriInterface;

final class SslConfigurator
{
    public function configure(UriInterface $uri, UriInterface $server, ?RoutingTable $table, DriverConfiguration $config): ?array
    {
        $sslMode = $config->getSslConfiguration()->getMode();
        $sslConfig = '';
        if ($sslMode === SslMode::FROM_URL()) {
            $scheme = $uri->getScheme();
            $explosion = explode('+', $scheme, 2);
            $sslConfig = $explosion[1] ?? '';
        } elseif ($sslMode === SslMode::ENABLE()) {
            $sslConfig = 's';
        } elseif ($sslMode === SslMode::ENABLE_WITH_SELF_SIGNED()) {
            $sslConfig = 'ssc';
        }

        if (str_starts_with($sslConfig, 's')) {
            // We have to pass a different host when working with ssl on aura.
            // There is a strange behaviour where if we pass the uri host on a single
            // instance aura deployment, we need to pass the original uri for the
            // ssl configuration to be valid.
            if ($table && count($table->getWithRole()) > 1) {
                return $this->enableSsl($server->getHost(), $sslConfig, $config);
            }

            return $this->enableSsl($uri->getHost(), $sslConfig, $config);
        }

        return null;
    }

    private function enableSsl(string $host, string $sslConfig, DriverConfiguration $config): ?array
    {
        $options = [
            'verify_peer' => $config->getSslConfiguration()->isVerifyPeer(),
            'peer_name' => $host,
        ];
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $options['SNI_enabled'] = true;
        }
        if ($sslConfig === 's') {
            return $options;
        }

        if ($sslConfig === 'ssc') {
            $options['allow_self_signed'] = true;

            return $options;
        }

        return null;
    }
}
