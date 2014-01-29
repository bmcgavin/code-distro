# code-distro - send code over 0mq

# What?

Distribute code patches over 0mq.

# Why?

Got a central git repository? Got AWS servers you don't want to give
access to said repository? Got hooks? This will help.

# How ?

This is a work in progress, but the theory is sound.

You will need: 

PHP zmq extension
Composer
Patience
A pair of web servers, one with repository access and one without

Github can send hooks based on pushes. Set up a repository hook on a machine that can communicate with Github, using src/ReceiveHook.php as a starting point.

Trying a push now...
And again
Final time
Arg, forgot the .gitrevision
