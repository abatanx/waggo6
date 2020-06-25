#!/bin/sh
cd `dirname $0`
cat data/example.sql | sed -n -e '/^-- @BEGIN.*MY/, /^-- @END/p' | sed -e 's/"/`/g' | sed -e 's/USING BTREE//gi'
