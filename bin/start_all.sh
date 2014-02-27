#!/bin/bash

/usr/bin/php bin/server.php &> /dev/null &
/usr/bin/php bin/client.php GithubHook &> /dev/null &
/usr/bin/php bin/client.php GithubPatch &> /dev/null &
/usr/bin/php bin/client.php Complete &> /dev/null &
