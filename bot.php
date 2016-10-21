<?php
$base     = dirname(__FILE__);
require_once $base . '/core/autoload.php';

/* Settings */
$list = array();

/* End of Settings */

$discord->on('ready', function ($discord) {
    global $base, $bot, $server, $list, $cleanlist, $conn;

    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
    	global $base, $bot, $server, $list, $conn, $admins;

        $cmd = strtolower($message->content);

        echo "{$message->author->username}#{$message->author->id}: {$message->content}",PHP_EOL;

        /* VARS */
        $officialTeams = FormatTeams($list, "name", $bot['mode'], $bot['qlstats']);

        /* ADMIN COMMANDS */
        if (in_array($message->author->id, $bot['admins'])) {
            if (0 === strpos($cmd, "!fakeadd")) {
                $var = explode(' ', $cmd);

                if (!isset($var[1]) || !is_numeric($var[1])) $message->reply("SYNTAX: !fakeadd <1-7>. Always make sure to add the number of bots then add yourself as a filler!");
                else {
                    $i = 0;
                    while ($i <= (int) $var[1]) {
                        $list["Bot" . $i]['elo']['duel']    = rand(1500, 2000);
                        $list["Bot" . $i]['elo']['tdm']     = rand(1500, 2000);
                        $list["Bot" . $i]['elo']['ctf']     = rand(1500, 2000);
                        $list["Bot" . $i]['elo']['ca']      = rand(1500, 2000);
                        $list["Bot" . $i]['name']           = "Bot" . $i;
                        $list["Bot" . $i]['discord']        = "Bot" . $i;
                        $i++;
                    }
                    $message->reply("You have succesfully added " . (int) $var[1] . " bots!");
                    
                }
            }


            if (0 === strpos($cmd, "!qlstats")) {
                $var = explode(' ', $cmd);

                if ($bot['qlstats'] == true) 
                    $val = "on";
                else 
                    $val = "off";

                if (!isset($var[1])) $message->reply("SYNTAX: !qlstats <on/off>. QLStats is currently turned set to " . $val . "!");
                else {

                    if ($var[1] == "on") {
                        $message->reply("QLStats has been turned on!");
                        $bot['qlstats'] = true;
                    } else {
                        $message->reply("QLStats has been turned off!");
                        $bot['qlstats'] = false;
                    }
                    
                }
            }

            if (0 === strpos($cmd, "!server")) {
                $var = explode(' ', $cmd);

                if (!isset($var[1])) $message->reply("SYNTAX: !server <ip>. The current IP is set to " . $bot['server'] . "!");
                else {
                    $bot['server'] = $var[1];
                    $message->reply("The server IP has been set to " . $bot['server'] . "!");
                    
                }
            }
        }
        /* END OF ADMIN CMDS */

        if (0 === strpos($cmd, "!add") || 0 === strpos($cmd, "!a")) {
            if (!isset($list[$message->author->username])) {
                /* Get the Steam ID Of the User */
                $getSteam = $conn->prepare("SELECT COUNT(u_id) AS rowCount, u_steam FROM users WHERE u_discord = ?");
                $getSteam->execute(array($message->author->id));
                $getData = $getSteam->fetch();

                if ($getData['rowCount'] <= 0 && $bot['qlstats'] == true) $message->reply("You do not have an account, please use **!create <steam64id>**!");
                else {

                    $list[$message->author->username]['name']           = $message->author->username;
                    $list[$message->author->username]['discord']        = $message->author;

                    if ($bot['qlstats'] == true) {
                        $url = "http://qlstats.net/elo/" . $getData['u_steam'] . "";
                        $result = file_get_contents($url);
                        $stats = json_decode($result, true);

                        $duel = $stats['players']['0']['duel']['elo'];
                        $tdm = $stats['players']['0']['tdm']['elo'];
                        $ctf = $stats['players']['0']['ctf']['elo'];
                        $ca = $stats['players']['0']['ca']['elo'];
                        $ffa = $stats['players']['0']['ffa']['elo'];
                        $ft = $stats['players']['0']['ft']['elo'];

                        $list[$message->author->username]['elo']['duel']    = $duel;
                        $list[$message->author->username]['elo']['tdm']     = $tdm;
                        $list[$message->author->username]['elo']['ctf']     = $ctf;
                        $list[$message->author->username]['elo']['ca']      = $ca;
                        $list[$message->author->username]['elo']['ffa']     = $ffa;
                        $list[$message->author->username]['elo']['ft']      = $ft;
                    }

                    if (count($list) == $bot['maxPlayers']) {
                        $officialDiscord = FormatTeams($list, "discord", $bot['mode'], $bot['qlstats']);
                        if ($bot['qlstats'] == true) {
                            $message->sendMessageAll("
The pickup has started!
" . $officialDiscord . "
/connect " . $bot['server'] . "!
                            ");
                            $list = array();
                        } else {
                            /* Just do Standard Listing */
                            foreach ($list as $value) {
                                if ($bot['qlstats'])
                                    $formatWho .= $prefixWho . '' . $value['discord'] . ' (' . $value['elo'][$bot['mode']] . ')';
                                else
                                    $formatWho .= $prefixWho . '' . $value['discord'];
                                $prefixWho = ', ';
                            }

                            /* Find 2 people that have the most reputation
                            $getMostReputation = $conn->prepare("SELECT u_positive, u_negative FROM users WHERE u_discord = ?");
                            $getMostReputation->execute(array($message->author->id));
                            */

                            $message->sendMessageAll("
The pickup has started, unfortunately due to QLStats being down we will be assigning captains for this PUG!

    **Players:** " . $formatWho . "

/connect " . $bot['server'] . "!
                            ");
                            $list = array();
                        }
                    } else { $message->sendMessageAll($message->author . " has been added to the queue! **" . count($list) . "/" . $bot['maxPlayers'] . "**"); }

                }
                
            } else {
                $message->reply("You have already been added to the queue!");
            }
        } 

        if (0 === strpos($cmd, "!remove") || 0 === strpos($cmd, "!r")) {
            if (isset($list[$message->author->username])) {
                unset($list[$message->author->username]);
                $message->sendMessageAll($message->author . " has been removed from the queue! **" . count($list) . "/" . $bot['maxPlayers']. "**");
                
            } else {
                $message->reply("you have to be added to the queue in order to remove yourself!");
            }
        } 

        if ($cmd == "!who" || $cmd == "!w") {
            foreach ($list as $value) {
                $prefixWho = "";
                $formatWho = $prefixWho;
                if ($bot['qlstats'])
                    $formatWho .= $prefixWho . '' . $value['name'] . ' (' . $value['elo'][$bot['mode']] . ')';
                else
                    $formatWho .= $prefixWho . '' . $value['name'];
                $prefixWho = ', ';
            }

            $whoTeams = FormatTeams($list, "name", $bot['mode'], $bot['qlstats']);

            if (empty($list))
                $message->reply("There is currently no one added, be the first by typing **!a(dd)**.");
            else {
                $message->reply("There is currently **" . count($list) . "/" . $bot['maxPlayers'] . "** added! 
                    " . $whoTeams
                );
            }
        }


        if ($cmd == "!help" || $cmd == "!h") {
            $message->reply("
        Welcome to **" . $bot['name'] . " v" . $bot['version'] . "**

        **General Commands:**
        !a(dd)      - Adds you to the PUG Queue.
        !r(emove)   - Removes you from the PUG Queue.
        !w(ho)      - Tells you who is exactly in the PUG Queue.
        !v(ersion)  - Tells you the version of the bot as well as the recent changelog.
        !m(ode)     - Tells you which mode the bot is set to.
        !h(elp)     - The command you are currently viewing right now!

        **User Commands:**
        !elo        - Checks ELO of a user.
        !create     - Creates a user using your Discord information.

        Please report any bugs to **GNiK#8129**, but please do not spam him!
            ");
        }

        if ($cmd == "!version" || $cmd == "!v") {
            $message->reply("
        The current version of this bot is **v" . $bot['version'] . "**

        [CHANGELOG 10/21/2016 - 2:00 PM PDT]
         - Fixed bug with !server not showing IP.
         - Fixed bug with !w(ho) not showing the full list of who is added.
         - Added Freeze Tag ELO to !elo & !mode. (Now STFU @ph1ldo)
         - Added experimental code to !w(ho) so it returns a list of Red / Blue team.

        [CHANGELOG 10/18/2016 - 11:00 PM PDT]
        - Added admin commands (!server & !qlstats).
        - Made !elo not usable if QLStats is turned off.
        - Made !add not add ELO if QLStats is turned off.
        - When a PUG starts, if QLStats is turned off it will assign captains instead of making teams.
        - Made it so all commands are required to type at the beginning, and cannot be placed in mid-sentences.

        [CHANGELOG 10/17/2016 - 11:00 PM PDT]
        - Made !w(ho) not mention each user but instead of just show the users names.

        [CHANGELOG 10/15/2016 - 6:55 PM PDT]
        - Fixed bug where the server IP was defaulted to 255.255.255.255:24960
        - Changed max list from 2 to 8.
        - Added command !v(ersion).
            ");
        }

        if ($cmd == "!mode" || $cmd == "!m") {
            $message->reply("the current mode set for this bot is " . strtoupper($bot['mode']) . "!");
        }

        if (0 === strpos($cmd, "!elo")) {
            $var = explode(' ', $cmd);

            if ($bot['qlstats'] == false) $message->reply("QLStats has been disabled, therefore this command is disabled!");
            else if (!isset($var[1])) $message->reply("Please type the username of the player you are looking for!");
            else {

                $getSteam = $conn->prepare("SELECT COUNT(u_id) AS rowCount, u_name, u_steam FROM users WHERE u_name = ?");
                $getSteam->execute(array($var[1]));
                $getData = $getSteam->fetch();

                if ($getData['rowCount'] <= 0) $message->reply("The username you have entered does not exist, please try again!");
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
        **Duel:** " . $duel . "
        **TDM:** " . $tdm . "
        **CTF:** " . $ctf . "
        **CA:** " . $ca . "
        **FFA:** " . $ffa . "
        **FT:** " . $ft . "
        Thank you for using QLStats!
                    ");
                }
            }
        }

        if (0 === strpos($cmd, "!create")) {
            $var = explode(' ', $cmd);

            if (!isset($var[1])) $message->reply("**SYNTAX:** !create <steamid64>. Please insert your Steam ID after the command! https://steamid.io/");
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
        Welcome! You have successfully registered yourself for our PUGs and may use !a(dd). A few rules before you begin however!
        1. If you inserted a false / invalid Steam ID, please contact GNiK#8129 otherwise you are at risk for getting banned.
        2. Any type of aliasing will result in all of your Discord accounts being banned from our system.
        If you want to edit your settings for your Discord account, please refer to here.
        http://phyrgg.com/discordbot/
        Thanks!
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