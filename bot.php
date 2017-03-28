<?php

//CONFIG SETTINGS
$bot_name = "QLStats-bot";
$bot_token = "";
$mysql_host = "localhost";
$mysql_db = "qlstats-bot";
$mysql_user = "";
$mysql_pw = "";

$base = dirname(__FILE__);
require_once $base . '/vendor/autoload.php';
use Discord\Discord;

$discord = new Discord([
    'token' => $bot_token,
]);

/* Settings */
$list = array();

/* End of Settings */

$discord->on('ready', function ($discord) {
    global $base, $bot, $server, $list, $cleanlist, $conn;

    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
    	global $base, $bot, $server, $list, $conn, $admins, $bot_name, $mysql_host, $mysql_db, $mysql_user, $mysql_pw;

        $conn = new PDO("mysql:host={$mysql_host};dbname={$mysql_db}", "{$mysql_user}", "{$mysql_pw}");

        $cmd = strtolower($message->content);

        echo "{$message->author->username}#{$message->author->id}: {$message->content}",PHP_EOL;

        if ($cmd == "!help" || $cmd == "!h") {
            $message->reply("
        Welcome to **{$bot_name}**!
                
        **Commands:**              
        !h(elp)     - The command you are currently viewing right now!        
        !create     - Creates a user using your Discord information (you need to do this before using !elo on your username).
        !elo        - Checks ELO of a user.                
            ");
        }


        if (0 === strpos($cmd, "!elo")) {
            $var = explode(' ', $cmd);

            if (!isset($var[1])) $var[1] = $message->author->username;
            $getSteam = $conn->prepare("SELECT * FROM users WHERE u_name = ?");
            $getSteam->execute(array($var[1]));
            $getData = $getSteam->fetch();

            if (!$getData) $message->reply("The username you have entered does not exist. You need to use !create if you haven't already!");
            else {
                $url = "http://qlstats.net/elo/" . $getData['u_steam'] . "";
                $result = file_get_contents($url);
                $array = json_decode($result, true);

                $duel = $array['players']['0']['duel']['elo'];
                $tdm = $array['players']['0']['tdm']['elo'];
                $ctf = $array['players']['0']['ctf']['elo'];
                $ca = $array['players']['0']['ca']['elo'];
                $ffa = $array['players']['0']['ffa']['elo'];
                $ft = $array['players']['0']['ft']['elo'];

                $message->reply("
        Current Statistics About **" . $getData['u_name'] . "**. Powered by QLStats!
        
        **FFA:** " . $ffa . "
        **Duel:** " . $duel . "
        **TDM:** " . $tdm . "
        **CTF:** " . $ctf . "
        **CA:** " . $ca . "        
        **FT:** " . $ft . "
        
        Thank you for using QLStats!
                ");
            }
        }

        if (0 === strpos($cmd, "!create")) {
            $var = explode(' ', $cmd);

            if (!isset($var[1])) $message->reply("**SYNTAX:** !create <steamid64>. Please insert your Steam ID after the command! You can get it from: https://steamid.io/");
            else if (isset($var[1]) && strlen($var[1]) < 16) $message->reply("The Steam64 ID you have entered is invalid, please try again!");
            else {

                $checkAccount = $conn->prepare("SELECT COUNT(u_id) FROM users WHERE u_discord = ?");
                $checkAccount->execute(array($message->author->id));

                if ($checkAccount->fetchColumn() > 0) $message->reply("You already have an account registered!");
                else if (strlen($var[1]) < 16) $message->reply("The Steam64 ID you have entered is invalid, please try again!");
                else {

                    $addAccount = $conn->prepare("INSERT INTO users ( u_discord, u_name, u_steam ) VALUES ( ?, ?, ? )");
                    $addAccount->execute(array($message->author->id, $message->author->username, $var[1]));

                    if ($addAccount->rowCount() > 0)
                        $message->reply("
        Welcome to {$bot_name}!
            You have successfully registered and can now check your elo with !elo.
                        ");
                }
            }
        }

        
    });
});


$discord->run();

if (isset($_GET['close'])) {
    $discord->close();
}


?>
