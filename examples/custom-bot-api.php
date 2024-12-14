<?php

declare(strict_types=1);

use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::init(
    '123456:124334534534', // tg bot token
    -14943993494, // tg chat id
    false, // debug mode
    'https://tg-bot-api.php-core.com' // custom Bot API server url
)->sendMessage('Test message');
