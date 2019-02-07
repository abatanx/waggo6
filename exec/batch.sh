#!/bin/sh

if [ "$1" = "" ]; then
	echo "Usage: $0 [filename]"
	exit
fi

if [ ! -f "$1" ]; then
	echo "File not found."
	exit
fi

. "$1"
