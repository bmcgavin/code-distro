## code-distro - send code over 0mq

## What?

Distribute code patches over 0mq.

## Why?

Got a central git repository? Got AWS servers you don't want to give
access to said repository? Got hooks? This will help.

## How ?

Most code hosting services (for Git, anyway) can send hooks based on pushes. Set up a repository hook on a machine that can communicate with (for example) Github, using src/ReceiveHook.php as a starting point.

GithubHook should run on the  machine with repository access ('public').
It will receive the hook, fetch the repo, and do the diff 
between the commits to generate the patch

GithubPatch should run on the machine without repo access ('private'). 
It will receive the patch, check that the working copy matches in branch and revision, and apply the patch.

## Quickstart

You will need: 

* PHP zmq extension
* Composer
* Patience
* (at least) a pair of servers, one public and 1-n private.
* A complete unwillingness to allow your private machine to talk to your code hosting service, _even with SSL and public keys_ and other security mod cons. Hey, maybe it's dynamic IP or something.

On public:

1. Set up a webserver
2. Put ReceiveHook.php in it
3. Point Github's receive hook at ReceiveHook.php
4. Run the main server : 

    bin/server.php config.php

5. Probably you want to run the hook processor here, too :

    bin/server.php config.github\_hook.php

On private:

1. Make sure the public machine is accessible via tcp broker ports (0MQ defaults : 5555 and 5557)
2. Get a copy of your repo checked out to a directory, and specify that in config.github\_patch.php. Probably scping an archive will do that.
3. Run the patch processor :

    bin/server.php config.github\_patch.php

Then push some code to your centralised repository. You should see activity in /var/log/code\_distro/ on both servers.

## Todo

* All messages passed through Message
* Safety checks (empty patches; remotes match; SSL; encrypted/signed patches)
* Other broker support (ActiveMQ / RabbitMQ / IronMQ? I've only used one of them).
* Other codehaus support (BitBucket / Gitlab)
* Branch support ? (deletion too) Probably an interesting one to solve. Can only really track the branch that the private server has.
* Better Processor definitions. They're sort of clients?

## Done

* Better process execution (exec() is not great)
* Script to re/start all three servers in one
