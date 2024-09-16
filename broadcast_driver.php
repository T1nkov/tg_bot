<!-- 
 ______   _______  _______  _______  ______   _______  _______  _______ _________
(  ___ \ (  ____ )(  ___  )(  ___  )(  __  \ (  ____ \(  ___  )(  ____ \\__   __/
| (   ) )| (    )|| (   ) || (   ) || (  \  )| (    \/| (   ) || (    \/   ) (   
| (__/ / | (____)|| |   | || (___) || |   ) || |      | (___) || (_____    | |   
|  __ (  |     __)| |   | ||  ___  || |   | || |      |  ___  |(_____  )   | |   
| (  \ \ | (\ (   | |   | || (   ) || |   ) || |      | (   ) |      ) |   | |   
| )___) )| ) \ \__| (___) || )   ( || (__/  )| (____/\| )   ( |/\____) |   | |   
|/ \___/ |/   \__/(_______)|/     \|(______/ (_______/|/     \|\_______)   )_(   
                                                                                 
 -->
<?php

include 'class/Telegram.php';
include 'class/DatabaseConnection.php';

$config_file = require __DIR__ . '/config/config.php';
$bot_token = $config_file['bot_token'];
$telegram = new Telegram($bot_token);

$db = new DatabaseConnection($config_file);

$db->broadcastToAllByCron($telegram);
?>
