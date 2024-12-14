<?php

declare(strict_types=1);

use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::init(
    '123456:124334534534', // tg bot token
    -14943993494, // tg chat id
    false, // debug mode
    TGLog::API_BASE_URL, // custom Bot API server url
    'nohup wget' // the cli program to use for the HTTP request
)->sendMessage('Test message');
