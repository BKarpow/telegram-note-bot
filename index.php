<?php

define('__ROOT', __DIR__);
define('MAX_NOTES_LENGTH', 799);

require __DIR__ . "/vendor/autoload.php";



env_init();
$model_data = new MySql('note_data');
$model_status = new MySql('note_status');
$telegram = new Telegram(env('TELEGRAM_BOT_TOKEN'));
$chat_id = (env('DEBUG', false)) ? '666666' :$telegram->ChatID();
$text = $telegram->Text();
$user_name = ( $telegram->Username()) ?
        $telegram->Username() :
        (string) $telegram->FirstName() .' '. (string) $telegram->LastName();

//if (!env('DEBUG')){
//    error_reporting(0);
//}


try {
    $status = (int) getStatus($chat_id, $model_status);

    if (!$status){
        setStatus('WORK', $chat_id, $model_status);
        $status = 1;
    }
    switch ($status){
        case 1:
            if ($text === '/start'){
                send(help(), getKeyboard($telegram));
            }elseif($text === 'Додати'){
                setStatus('ADD', $chat_id, $model_status);
                send("Пишіть нотатку (не довше ".MAX_NOTES_LENGTH." символів) :");
            }
            break;
        case 3:
            if ($text === 'Назад'){
                setStatus('WORK', $chat_id, $model_status);
                send(help(), getKeyboard($telegram));
            }
            addNote($text, $chat_id, $model_data);
            setStatus('WORK', $chat_id, $model_status);
            send('Додано нотатку.', getKeyboard($telegram, true));
            break;
    }
}catch (ErrorException $e){
    if (env('DEBUG')){
        $telegram->sendMessage(['chat_id'=>$chat_id, 'text'=>$e->getMessage()]);
        die($e->getMessage());
    }else{
        die('Error');
    }


}








