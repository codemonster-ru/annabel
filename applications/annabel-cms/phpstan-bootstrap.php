<?php

require __DIR__ . '/vendor/autoload.php';

$helperPaths = [
    __DIR__ . '/vendor/codemonster-ru/annabel/src/helpers/*.php',
    __DIR__ . '/../../packages/framework/src/helpers/*.php',
];

foreach ($helperPaths as $pattern) {
    foreach (glob($pattern) ?: [] as $helper) {
        require_once $helper;
    }
}
