<?php

declare(strict_types=1);

/**
 * All of Scout configuration comes from `.env`.
 *
 * This `scout.php` file exists to automatically pull in all env vars into Laravel's configuration repository so that
 * it can be cached, and accessed via the "dot" notation used in the config repository.
 */

use Scoutapm\Config\ConfigKey;

return array_combine(
    ConfigKey::allConfigurationKeys(),
    array_map(
        static function ($configKey) {
            return env('SCOUT_' . strtoupper($configKey), null);
        },
        ConfigKey::allConfigurationKeys()
    )
);
