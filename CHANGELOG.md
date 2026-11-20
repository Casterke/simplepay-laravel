# Changelog

All notable changes to `simplepay-laravel` will be documented in this file

## 0.2.0 - 2026-11-20

- Support Laravel 11, 12 and 13
- PHP 8.4/8.5 compatibility: explicit nullable exception parameter, declared SDK properties (no dynamic properties), `curl_close()` only on PHP < 8.0
- Fix: IPN signature check fataled with "Cannot redeclare getallheaders()" on a second call in the same process where PHP has no `getallheaders()` (CLI, tests, queue workers)
- Fix: undeclared `transactionBase`/`returnData`/`request` properties on some SDK classes

## 1.0.0 - 2022-06-14

- initial release
