# screenerbot

Screener Bot is a Telegram bot that will take screen shot from the Ingress Intel map.
It can do simple shot or multi-shots rearange into a video.

Requires :
- Linux'style OS
- "at" command installed
- Apache and PHP properly configured with curl
- SSL & signed certificate (see let's encrypt)
- domain name for comfort

Configuration :
- All you need is to answer to every "define" in botConfig.php
- Path to include this file in IceManager.php and ScreenerBot.php

Many thanks to https://github.com/nibogd/ingress-ice and https://github.com/Eleirbag89/TelegramBotPHP
