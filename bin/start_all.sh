#!/bin/bash

/usr/bin/php bin/server.php &> /dev/null &
/usr/bin/php bin/client.php GithubHook &> /dev/null &
/usr/bin/php bin/client.php BitbucketHook &> /dev/null &
/usr/bin/php bin/client.php GitPatch &> /dev/null &
/usr/bin/php bin/client.php Complete &> /dev/null &
