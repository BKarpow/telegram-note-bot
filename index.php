<?php

define('__ROOT', __DIR__);
define('MAX_NOTES_LENGTH', 799);

require __DIR__ . "/vendor/autoload.php";

ini_set('date.timezone', env('TIMEZONE'));



env_init();

$model_data = new MySql('note_data');
$model_status = new MySql('note_status');
$telegram = new Telegram(env('TELEGRAM_BOT_TOKEN'));
$chat_id = $telegram->ChatID();
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
            }elseif($text === "Видалити"){
                setStatus('DELETE', $chat_id, $model_status);
                send('Вкажіть номер нотатки для видалення?', getKeyboard($telegram, true));
            }elseif ($text === "Всі нотатки"){
                $notes = getNotes($chat_id, $model_data);
                sendNotes($notes);
            }elseif($text === "Скоротити url"){
                send('Відправте url для скорочення.', getKeyboard($telegram, true));
                setStatus('SHORT', $chat_id, $model_status);
            }elseif($text === "Курси валют"){
                $r = getRate();
                send($r, getKeyboard($telegram, true));
            }else{
                send(help(), getKeyboard($telegram));
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
        case 4:
            if ($text === "Назад"){
                setStatus('WORK', $chat_id, $model_status);
                send(help(), getKeyboard($telegram));
            }else{
                $id = (int) $text;
                deleteNotes($id, $chat_id, $model_data);
                setStatus('WORK', $chat_id, $model_status);
                send("Видалено нотатку під номером ".$id);
                send(help(), getKeyboard($telegram));
            }
            break;
        case 5:
            if ($text === "Назад"){
                setStatus('WORK', $chat_id, $model_status);
                send(help(), getKeyboard($telegram));
            }else{
                $short = shortUrl($text);
                send($short);
                setStatus('WORK', $chat_id, $model_status);
                send("Скорочено. ");
                send(help(), getKeyboard($telegram));
            }

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








