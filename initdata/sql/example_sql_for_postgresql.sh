#!/bin/sh
cd `dirname $0`
cat data/example.sql | sed -n -e '/^-- @BEGIN.*PG/, /^-- @END/p'
