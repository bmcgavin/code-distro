#!/bin/bash

/usr/bin/php bin/server.php config.php &> /dev/null &
/usr/bin/php bin/server.php config.github_hook.php &> /dev/null &
/usr/bin/php bin/server.php config.github_patch.php &> /dev/null &
