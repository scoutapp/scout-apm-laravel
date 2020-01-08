<?php

declare(strict_types=1);

/**
 * All of Scout configuration comes from `.env`.
 *
 * This `scout_apm.php` file exists to automatically pull in all env vars into Laravel's configuration repository so
 * that it can be cached, and accessed via the "dot" notation used in the config repository.
 */

use Scoutapm\Config\ConfigKey;

/** @noinspection PhpUnnecessaryLocalVariableInspection */
$config = array_combine(
    ConfigKey::allConfigurationKeys(),
    array_map(
        static function ($configKey) {
            return env('SCOUT_' . strtoupper($configKey), null);
        },
        ConfigKey::allConfigurationKeys()
    )
);

/**
 * If you want to override specific keys that were set in your env (or you don't want to use `.env` to configure), you
 * can do so here; these will take precedence.
 */
// $config['key'] = 'actuallyThisOne';

return $config;
