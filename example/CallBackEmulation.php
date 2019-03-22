<?php

/*
 * Пример скрипта, который эмулирует CallBack через LongPoll.
 * Требуется Memcache и VDS/VPS!
 * */

require __DIR__ . '/VKCallBackBot/VKCallBackBot.php';

$bot = new VKCallBackBot([
    'access_token' => 'null',
    'confirmation_key' => 'null',
    'fwdMessagesProcess' => true,
    'group_id' => 0,
    'WebServer' => 1,
    'vAPI' => '5.92',
]);

//Фаил в котором находится обработчик событий
$handler = __DIR__ . '/treated.php';

while (true) {
    $events = $bot->events->get();

    foreach ($events as $event) {
        $mc_key = 'event_' . mt_rand();
        $bot->mc->add($mc_key, $event, false, 5);

        exec('nohup php '.$handler.' "' . $mc_key . '" >> /dev/null 2>&1 & echo $!');
    }
}