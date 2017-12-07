<?php

use Noodlehaus\Config;

if (! function_exists('decodeMessage')) {
    function decodeMessage($message)
    {
        $result = json_decode($message);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        } else {
            return $message;
        }
    }
}


if (! function_exists('config')) {
    function config()
    {
        $config = new Config(__DIR__ . '/../config');
        return $config;
    }
}
