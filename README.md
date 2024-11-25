# Simple Telegram Log
### A simple Telegram logging helper for PHP

## Usage

```shell
composer require php-core/simple-telegram-log
```

### Examples:

#### Using environment vars (short usage)

```php
use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__) . '/vendor/autoload.php';

TGLog::logMessage('Test message');
```

#### In code (flexible usage)

```php
use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__) . '/vendor/autoload.php';

TGLog::init(
    '123456:124334534534', // tg bot token
    -14943993494, // tg chat id
    false // debug mode
)->sendMessage('Test message');
```

#### Logging a PHP "Exception/Throwable"
```php
use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::logException(new Exception('Test exception'));
```

#### Log request dump
```php
use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::logRequestDump();
```

#### Debug log
```php
use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::debugLogMessage('This message will only be logged if debug mode is on');
```

#### Optional Environment Variables for simple use:

| Variable         | Default | Description                                           |
|------------------|---------|-------------------------------------------------------|
| DEBUG            | false   | "true" or "false" enables or disables debug mode      |
| TG_LOG_BOT_TOKEN | x       | The default bot token to use for sending log messages |
| TG_LOG_CHAT_ID   | x       | The default chat ID to send log messages to           |
