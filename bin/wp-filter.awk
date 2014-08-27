# Add single quotes back to arguments with spaces,
# because bash's $@ syntax removes them.
# 
# Wraps in single quotes, because Bash allows escaping of double-quotes, but not single.
# For example, bash doesn't allow this:
#   'Escaping \'single quotes\''
# 
# So we're unlikely to get an argument with single quotes in it.
#   Edge case: user goes to great lengths, concatenating strings.
#   See http://stackoverflow.com/questions/1250079/escaping-single-quotes-within-single-quoted-strings
#   
# If a user escapes double quotes, like this:
#   "Escaping \"double quotes\""
#   
# The string will come through as:
#   Escaping "double quotes"
#   
# Which will be converted back to:
#   'Escaping "double quotes"'
#   
# Which is fine.

{
	# Check if argument contains spaces.
	# Space escapes have already been processed.
	if ( $0 ~ / / ) {

		# Is it a flag argument following --flag=value syntax?
		if ( $0 ~ /--([a-zA-Z0-9_-]+)=(.*)/ ) {
			
			# Add single quote after equals sign
			gsub( "=", "='", $0 );

			# Add single quote at end
			print( $0 "'" )

		}else {
			# Normal argument. Wrap in single quotes
			print( "'" $0 "'" )
		}

	}else {

		# No unescaped spaces. Print as-is
		print( $0 )

	}

}