#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
QUOTED_ARGS=''

if [ "$1" == "test-wp-filter" ]; then
	echo ""
fi

for SINGLE_ARG in "$@"; do 

	# $@ argument expansion removes quotes.
	# Re-quote if they need it, giving special treatment to --flag=value syntax.
	
	FIXED=$( echo "$SINGLE_ARG" | awk -f "$DIR/wp-filter.awk" )

	# Test script:
	# 	 wp test-wp-filter escaped\ space --long_flag-name="foo bar" --single-quotes='foo bar' --escaped=foo\ bar --no-equals "foo bar" no quotes here  "Escaping \"double quotes\" is fine"
	# 	
	# Expected output:
	# 	'escaped space'
	# 	--long_flag-name='foo bar'
	# 	--single-quotes='foo bar'
	# 	--escaped='foo bar'
	# 	--no-equals
	# 	'foo bar'
	# 	no
	# 	quotes
	# 	here
	# 	'Escaping "double quotes" is fine'

	if [ "$1" == "test-wp-filter" ]; then
		echo "$FIXED"
	else
		QUOTED_ARGS="$QUOTED_ARGS $FIXED"
	fi

done;

if [ "$1" == "test-wp-filter" ]; then
	echo ""
else
	vagrant ssh -c "cd /vagrant/www/wp; /usr/bin/wp $QUOTED_ARGS" -- -q
fi
