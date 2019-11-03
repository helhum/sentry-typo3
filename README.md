# Sentry TYPO3 Integration

Exception and error logging with Sentry, see http://www.getsentry.com

The package is a wrapper for https://github.com/getsentry/sentry-php

## Installation

```bash
$ composer require helhum/sentry-typo3
```

## Configuration

Set the dsn in your global configuration. 
E.g.: `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['dsn'] = 'http://public_key:secret_key@your-sentry-server.com/project-id';`.
It is possible to change / add other Sentry options with `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'][<sentry option>]` 

Since the integration is purely done with log writers, make sure you add at least one
writer. It is recommended to add a global writer as follows:

```php
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::WARNING] = [
    \Helhum\SentryTypo3\Log\Writer\SentryWriter::class => [],
];
```

To get additional information for each error/warning logged, add the breadcrumb writer as well

```php
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::WARNING] = [
    \Helhum\SentryTypo3\Log\Writer\SentryBreadcrumbWriter::class => [],
];
```

## How to test the connection to Sentry?

Navigate to a reachable page with a not configured page type like 

http://your-typo3-site.de/index.php?id=1&type=1001 

This triggers a ServiceUnavailableException which will be reported.

## Improvements / Issues

This package is managed on GitHub. Feel free to get in touch at
https://github.com/helhum/sentry-typo3
