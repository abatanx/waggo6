#!/bin/sh
cd `dirname $0`
cat data/core_template.sql | sed -n -e '/^-- @BEGIN.*MY/, /^-- @END/p' | sed -e 's/"/`/g' | sed -e 's/USING BTREE//gi'
