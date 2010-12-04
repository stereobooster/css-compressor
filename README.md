[CSS Compressor](http://www.codenothing.com/css-compressor/)
========================

PHP Based CSS Compressor.


Usage
-----

	$compressed = CSSCompression::express( $css, 'safe' );


Or, if you need to run it multiple times

	$CSSC = new CSSCompression( 'safe' );
	$compressed = $CSSC->compress( $css );


Modes
-----

Modes are pre-defined sets of options that can be set by passing in the mode name.

 - **safe**: Safe mode does zero combinations or organizing. It's the best mode if you use a lot of hacks

 - **sane**: Sane mode does all combinations(multiple long hand notations to single shorthand), but still keeps most declarations in their place.

 - **small**: Small mode reorganizes the whole sheet, combines as much as it can, and will break most comment hacks. 

 - **full**: Full does everything small does, but also converts hex codes to their short color name alternatives. This is only safe for latest browsers.


Here's a few different ways to initiate a mode.

	// Express with safe mode
	$compressed = CSSCompression::express( $css, 'safe' );

	// Creating new instance with sane mode
	$CSSC = new CSSCompression( 'sane' );

	// Setting an instance with small mode
	$CSSC->mode( 'small' );

	// Or compressing with the current instance, and setting full mode
	$compressed = $CSSC->compress( $css, 'full' );
	


Singleton Instances
-------------------

Yes the compressor provides singleton access(separate from express), but use it wisely.

	$CSSC = CSSCompression::getInstance();


Option Handling
---------------

The compressor has an option function attached to it, that has multiple functionalities. If no arguments are passed in,
the entire options array is returned. If a single name argument is passed, then the value of that key name in the options
array is returned. If both a name and value are passed, then that value is set to it's corresponding key in the array.

	// Returns entire options array
	$options = $CSSC->option();

	// Returns the readability value
	$readability = $CSSC->option( 'readability' );

	// Sets the readability to non-readable
	$CSSC->option( 'readability', CSSCompression::READ_NONE );


Additionally, a reset function is provided to revert back to base options (decided at runtime).

	// Resets options to original values
	$CSSC->reset();



Readability
-----------

The compressor class provides static integers that map to the internal readability values

	CSSCompression::READ_MAX // Maximum Readability
	CSSCompression::READ_MED // Medium readability of output
	CSSCompression::READ_MIN // Minimal readability of output
	CSSCompression::READ_NONE // No readability of output (full compression into single line)

	// To set maximum readability (Assuming you have your own instance)
	$CSSC->option( 'readability', CSSCompression::READ_MAX );

	// Or, just pass it in as another option
	$options = array(
		'readability' =>CSSCompression::READ_MAX,
		// Other options ...
	);
	// Get full readability through express
	$compressed = CSSCompression::express( $css, $options );
