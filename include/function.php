<?php

/**
 * Повертає список статусів
 * @return array
 */
function getStatusList():array
{
    return [
        'WORK' => 1,
        'PASSWORD' => 2,
        'TITLE' => 3
    ];
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
