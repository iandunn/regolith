<?php

/** @var array $config */

// 10 minutes is not enough for a small sites, especially since Surge doesn't preload.
// See https://github.com/kovshenin/surge/issues/21.
$config['ttl'] = 60 * 60 * 24 * 7;

return $config;
