#!/bin/bash

pslist=`ps ax -o 'pid,command' | grep -v grep | grep php | grep client.php | awk '{ print $1 }'`

for pid in $pslist
do
    kill $pid
done
pslist=`ps ax -o 'pid,command' | grep -v grep | grep php | grep server.php | awk '{ print $1 }'`

for pid in $pslist
do
    kill $pid
done