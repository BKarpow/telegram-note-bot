<?php

/**
 * Просте логування
 * @param $text
 * @param string $level
 * @param string $file
 */
function log_bot($text, $level = 'DEBUG', $file = 'debug.log')
{
    $text = now() ." [$level]: $text ".PHP_EOL;
    file_put_contents(__ROOT .'/'.$file, $text, FILE_APPEND);
}

/**
 * Повертає список статусів
 * @param integer $code_status
 * @return array|string
 */
function getStatusList(int $code_status = 0)
{
    $statuses = [
        'WORK' => 1,
        'SEARCH' => 2,
        'ADD' => 3
    ];
    if ($code_status){
        foreach ($statuses as $status => $code) {
            if ($code_status === $code) {
                return $status;
            }
        }
    }
    return $statuses;
}

/**
 * ПовертаЄ масив з готовою клавіатурою
 * @param Telegram $telegram
 * @param boolean $back
 * @return false|string
 */
function getKeyboard(Telegram $telegram, bool $back = false)
{
    $option = array(
        //First row
        array($telegram->buildKeyboardButton("Всі нотатки"), $telegram->buildKeyboardButton("Знайти")),
        //Second row
        array($telegram->buildKeyboardButton("Додати"), $telegram->buildKeyboardButton("Button 4"), $telegram->buildKeyboardButton("Button 5")),
        //Third row
        array($telegram->buildKeyboardButton("Button 6")) );
    if ($back){
        $option = [
            [$telegram->buildKeyboardButton("Назад")]
        ];
    }
    return $telegram->buildKeyBoard($option, $onetime=false);
}

/**
 * Повертає рядок з датою
 * @return false|string
 */
function now()
{
    return date(env('FORMAT_DATE'));
}

/**
 * Ініціює файл .emv
 * @return void
 */
function env_init()
{
    // Init .env file
    $dotenv = Dotenv\Dotenv::createImmutable(__ROOT);
    $dotenv->load();
}


/**
 * Повертає опціїї зі змінного оточення
 * @param string $option
 * @param null $default
 * @return mixed|null
 */
function env(string $option, $default = null)
{
    $res = $_ENV[$option] ?? $default;
    if (strtolower($res) === "true" || strtolower($res) === "ok" || strtolower($res) === "yes"){
        $res = true;
    }elseif(strtolower($res) === "false" || strtolower($res) === "not" || strtolower($res) === "np"){
        $res = false;
    }elseif(preg_match('#^[\d]+$#si', $res)){
        $res = (int) $res;
    }
    return $res;
}


/**
 * Повертає статус бота
 * @param string $chat_id
 * @param MySql $mySql - модель таблиці ствтусів
 * @return string
 */
function getStatus(string $chat_id, MySql $mySql)
{
    $res = $mySql->where('chat_id', $chat_id);
    return (string) $res[0]['status'];
}

/**
 * Оновлює статус
 * @param string $status
 * @param string $chat_id
 * @param MySql $mySql
 * @return array|bool
 */
function setStatus(string $status, string $chat_id, MySql $mySql)
{

    if (empty($mySql->where('chat_id', $chat_id)[0])){
        return $mySql->insert([
            'chat_id' => $chat_id,
            'status' => getStatusList()[$status],
            'date' => now()
        ]);
    }else{

        return $mySql->update([
            'status' => getStatusList()[$status],
            'date' => now()
        ], " `chat_id` = '{$chat_id}' ");
    }
}

/**
 * Обгортка для відправки повідомлень
 * @param string $text
 * @param array|null $keyboard
 * @return mixed
 */
function send(string $text, $keyboard = null)
{
    global $telegram, $chat_id;
    $content = ['chat_id'=>$chat_id,'text'=>$text];
    if (!is_null($keyboard)){
        $content['reply_markup'] = $keyboard;
    }
    return $telegram->sendMessage($content);
}

/**
 * Повертає привітання
 * @return string
 */
function help()
{
    return "Привіт, даний бот дає змогу вести нотатки.".PHP_EOL
        ."Навіщо його робив незнаю.".PHP_EOL
        ."Автор: Богдан Карпов @BogdanKarpov";
}

/**
 * Додає нову нотатку
 * @param string $note
 * @param string $chat_id
 * @param MySql $model
 * @return array|bool
 */
function addNote(string $note, string $chat_id, MySql $model)
{
    $note = strip_tags(trim($note));
    if (strlen($note) >= MAX_NOTES_LENGTH){
        $note = substr($note, 0, MAX_NOTES_LENGTH - 3) . '...';
    }
    return $model->insert([
        'chat_id' => $chat_id,
        'note' => $note
    ]);
}

/**
 * Повертає масив нотаток
 * @param string $chat_id
 * @param MySql $model
 * @return array
 */
function getNotes(string $chat_id, MySql $model)
{
    return (array) $model->where('chat_id', $chat_id);
}

/**
 * Відправляє список в телеграм
 * @param array $data
 */
function sendNotes(array $data)
{
    global $telegram;
    foreach ($data as $datum) {
        $n = "Нотатка № {$datum['id']}: ".PHP_EOL
            ."{$datum['note']}".PHP_EOL.PHP_EOL
            ."Дата: {$datum['date']}.";
        send($n, getKeyboard($telegram, true));
    }
}
