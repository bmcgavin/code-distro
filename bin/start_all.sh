#!/bin/bash

/usr/bin/php bin/server.php &> /dev/null &
/usr/bin/php bin/client.php github_hook &> /dev/null &
/usr/bin/php bin/client.php github_patch &> /dev/null &
/usr/bin/php bin/client.php complete &> /dev/null &
