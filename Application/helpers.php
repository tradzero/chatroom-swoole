<?php

if (! function_exists('decodeMessage')) {
    function decodeMessage($message) {
        $result = json_decode($message);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        } else {
            return $message;
        }
    }
}