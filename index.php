<?php

define('__ROOT', __DIR__);

require __DIR__ . "/vendor/autoload.php";

env_init();
$model_data = new MySql('note_data');
$model_status = new MySql('note_status');
$telegram = new Telegram(env('TELEGRAM_BOT_TOKEN'));
$chat_id = (env('DEBUG', false)) ? '666666' :$telegram->ChatID();
$user_name = ( $telegram->Username()) ?
        $telegram->Username() :
        (string) $telegram->FirstName() .' '. (string) $telegram->LastName();

if (!env('DEBUG')){
    error_reporting(0);
}

try {
    $status = (int) getStatus($chat_id, $model_status);

    if (!$status){
        setStatus('WORK', $chat_id, $model_status);
    }
    switch ($status){
        case 1:

            break;
    }
}catch (ErrorException $e){
    die($e->getMessage());
}








