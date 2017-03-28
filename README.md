**NOTE: WORK IN PROGRESS! INCOMPLETE!**

# QLStats-Discord Minimal
This is a fork of QLStats-Discord. I have ripped out the pickup group stuff since I was interested just in the ELO functionality.

### Requirements
- PHP
- MySQL

### Installation/Configuration
- Go to https://discordapp.com/developers/applications/me and hit "New App". Call the bot what you like (QLStats-bot for instance). You can also set the bots avatar here. Then hit "Create App". On the next screen hit "Create a Bot User". Once the app bot user is created, hit "click to reveal" under the Token field for the bot and note the token here for later.
- Create a DB in MySQL called "qlstats-bot". Create a table called "users" with 4 fields: id (int autoincrement primary), u_discord (text), u_name (text), u_steam (text).
- Create a folder under /home/yourusername, let's say "qlstats-bot" and enter it
- Type: sudo apt install composer
- sudo composer require team-reflex/discord-php
- sudo wget https://raw.githubusercontent.com/roasticle/QLStats-Discord-Minimal/master/bot.php 
- edit bot.php and set your configuration options at the top of the file
- sudo php bot.php
- bot should start up and you can test a commmand on your Discord server!


### Commands 
**!elo** - Grabs ELO of a registered player in the database.  
**!h(elp)** - General Help that displays all of the commands.  
**!create** - Creates an account in the database to record your ELO.   

### Credits
 - Discord-PHP by @teamreflex  
 - CameronCT
 - educator's mom
