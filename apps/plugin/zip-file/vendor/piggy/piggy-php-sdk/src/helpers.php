<?php

namespace Piggy\Api;

use GuzzleHttp\ClientInterface;

/**
 * Tests whether the installed Guzzle version is v5 or later.
 *
 * @return bool Whether Guzzle v5 is present (true) or not (false).
 */
function hasGuzzle5(): bool
{
    // Pre Guzzle v6 all Clients had a full VERSION string. After they have an
    // integer indicating the MAJOR_VERSION.
    if (defined('GuzzleHttp\ClientInterface::VERSION')) {
        if (ClientInterface::VERSION < 6) {
            return true;
        }
    }

    return false;
}
