#!/bin/sh
cd `dirname $0`
cat data/core_template.sql | sed -n -e '/^-- @BEGIN.*PG/, /^-- @END/p'
