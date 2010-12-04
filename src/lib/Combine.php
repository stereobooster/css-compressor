<?php
/**
 * CSS Compressor [VERSION]
 * [DATE]
 * Corey Hart @ http://www.codenothing.com
 */ 

Class CSSCompression_Combine
{
	/**
	 * Combine Patterns
	 *
	 * @class Control: Compression Controller
	 * @param (string) token: Copy of the injection token
	 * @param (array) options: Reference to options
	 * @param (regex) rspace: Checks for space without an escape '\' character before it
	 * @param (regex) rcsw: Border/Outline matching
	 * @param (regex) raural: Aurual matching
	 * @param (regex) rmpbase: Margin/Padding base match
	 * @param (regex) rmp: Margin/Padding matching
	 * @param (regex) rborder: Border matching
	 * @param (regex) rfont: Font matching
	 * @param (regex) rbackground: Background matching
	 * @param (regex) rlist: List style matching
	 * @param (regex) rimportant: Checking props for uncombinables
	 * @param (array) methods: List of options with their corresponding handler
	 */
	private $Control;
	private $token = '';
	private $options = array();
	private $rspace = "/(?<!\\\)\s/";
	private $rcsw = "/(^|(?<!\\\);)(border|outline)-(color|style|width):(.*?)(?<!\\\);/";
	private $raural = "/(^|(?<!\\\);)(cue|pause)-(before|after):(.*?)(?<!\\\);/";
	private $rmpbase = "/(margin|padding):(.*?)(?<!\\\);/";
	private $rmp = "/(^|(?<!\\\);)(margin|padding)-(top|right|bottom|left):(.*?)(?<!\\\);/";
	private $rborder = "/(^|(?<!\\\);)(border)-(top|right|bottom|left):(.*?)(?<!\\\);/";
	private $rfont = "/(^|(?<!\\\);)(font|line)-(style|variant|weight|size|height|family):(.*?)(?<!\\\);/";
	private $rbackground = "/(^|(?<!\\\);)background-(color|image|repeat|attachment|position):(.*?)(?<!\\\);/";
	private $rlist = "/(^|(?<!\\\);)list-style-(type|position|image):(.*?)(?<!\\\);/";
	private $rimportant = "/inherit|\!important|!ie|\s/i";
	private $methods = array(
		'csw-combine' => 'combineCSWproperties',
		'auralcp-combine' => 'combineAuralCuePause',
		'mp-combine' => 'combineMPproperties',
		'border-combine' => 'combineBorderDefinitions',
		'font-combine' => 'combineFontDefinitions',
		'background-combine' => 'combineBackgroundDefinitions',
		'list-combine' => 'combineListProperties',
	);

	/**
	 * Stash a reference to the controller on each instantiation
	 *
	 * @param (class) control: CSSCompression Controller
	 */
	public function __construct( CSSCompression_Control $control ) {
		$this->Control = $control;
		$this->token = $control->token;
		$this->options = &$control->Option->options;
	}

	/**
	 * Reads through each detailed package and checks for cross defn combinations
	 *
	 * @param (array) selectors: Array of selectors
	 * @param (array) details: Array of details
	 */
	public function combine( &$selectors = array(), &$details = array() ) {
		foreach ( $details as $i => &$value ) {
			if ( isset( $selectors[ $i ] ) && strpos( $selectors[ $i ], $this->token ) === 0 ) {
				continue;
			}

			foreach ( $this->methods as $option => $fn ) {
				if ( $this->options[ $option ] ) {
					$value = $this->$fn( $value );
				}
			}
		}

		return array( $selectors, $details );
	}

	/**
	 * Combines color/style/width of border/outline properties
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineCSWproperties( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->rcsw, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			if ( ! isset( $storage[ $match[ 2 ][ 0 ] ] ) ) {
				$storage[ $match[ 2 ][ 0 ] ] = array( $match[ 3 ][ 0 ] => $match[ 4 ][ 0 ] );
			}

			// Override double written properties
			$storage[ $match[ 2 ][ 0 ] ][ $match[ 3 ][ 0 ] ] = $match[ 4 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// Go through each tag for possible combination
		foreach ( $storage as $tag => $arr ) {
			// All three have to be defined
			if ( count( $arr ) == 3 && ! $this->checkUncombinables( $arr ) ) {
				$storage[ $tag ] = "$tag:" . $arr['width'] . ' ' . $arr['style'] . ' ' . $arr['color'] . ';';
			}
			else {
				unset( $storage[ $tag ] );
			}
		}

		// Now rebuild the string replacing all instances
		$pos = 0;
		while ( preg_match( $this->rcsw, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$prop = $match[ 2 ][ 0 ];
			if ( isset( $storage[ $prop ] ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $storage[ $prop ], $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $storage[ $prop ] ) - $colon - 1;
				$storage[ $prop ] = '';
			}
			else {
				$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Combines Aural properties (currently being depreciated in W3C Standards)
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineAuralCuePause( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->raural, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			if ( ! isset( $storage[ $match[ 2 ][ 0 ] ] ) ) {
				$storage[ $match[ 2 ][ 0 ] ] = array( $match[ 3 ][ 0 ] => $match[ 4 ][ 0 ] );
			}

			// Override double written properties
			$storage[ $match[ 2 ][ 0 ] ][ $match[ 3 ][ 0 ] ] = $match[ 4 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// Go through each tag for possible combination
		foreach ( $storage as $tag => $arr ) {
			// All three have to be defined
			if ( count( $arr ) == 2 && ! $this->checkUncombinables( $arr ) ) {
				$storage[ $tag ] = "$tag:" . $arr['before'] . ' ' . $arr['after'] . ';';
			}
			else {
				unset( $storage[ $tag ] );
			}
		}

		// Now rebuild the string replacing all instances
		$pos = 0;
		while ( preg_match( $this->raural, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$prop = $match[ 2 ][ 0 ];
			if ( isset( $storage[ $prop ] ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $storage[ $prop ], $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $storage[ $prop ] ) - $colon - 1;
				$storage[ $prop ] = '';
			}
			else {
				$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Explodes shorthanded margin/padding properties for later combination
	 *
	 * @param (string) val: Rule set
	 */
	private function mpbuild( $val ) {
		$pos = 0;
		while ( preg_match( $this->rmpbase, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$replace = '';
			$prop = $match[ 1 ][ 0 ];
			$value = preg_split( $this->rspace, trim( $match[ 2 ][ 0 ] ) );
			$positions = array(
				'top' => 0,
				'right' => 0,
				'bottom' => 0,
				'left' => 0
			);

			// Each position needs a value
			switch ( count( $value ) ) {
				case 1:
					$positions['top'] = $positions['right'] = $positions['bottom'] = $positions['left'] = $value[ 0 ];
					break;
				case 2:
					$positions['top'] = $positions['bottom'] = $value[ 0 ];
					$positions['right'] = $positions['left'] = $value[ 1 ];
					break;
				case 3:
					$positions['top'] = $value[ 0 ];
					$positions['right'] = $positions['left'] = $value[ 1 ];
					$positions['bottom'] = $value[ 2 ];
					break;
				case 4:
					$positions['top'] = $value[ 0 ];
					$positions['right'] = $value[ 1 ];
					$positions['bottom'] = $value[ 2 ];
					$positions['left'] = $value[ 3 ];
					break;
				default:
					continue;
			}

			// Build the replacement
			foreach ( $positions as $p => $v ) {
				$replace .= "$prop-$p:$v;";
			}
			$pos += strlen( $replace );
			$val = substr_replace( $val, $replace, $match[ 0 ][ 1 ], strlen( $match[ 0 ][ 0 ] ) );
		}

		return $val;
	}

	/**
	 * Combines multiple directional properties of 
	 * margin/padding into single definition.
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineMPproperties( $val ) {
		$storage = array();
		$val = $this->mpbuild( $val );

		// Find all possible occurences of margin/padding and mark their directional value
		$pos = 0;
		while ( preg_match( $this->rmp, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			if ( ! isset( $storage[ $match[ 2 ][ 0 ] ] ) ) {
				$storage[ $match[ 2 ][ 0 ] ] = array( $match[ 3 ][ 0 ] => $match[ 4 ][ 0 ] );
			}

			// Override double written properties
			$storage[ $match[ 2 ][ 0 ] ][ $match[ 3 ][ 0 ] ] = $match[ 4 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// Go through each tag for possible combination
		foreach ( $storage as $tag => $arr ) {
			// Only combine if all 4 definitions are found
			if ( count( $arr ) == 4 && ! $this->checkUncombinables( $arr ) ) {
				// All 4 are the same
				if ( $arr['top'] == $arr['bottom'] && $arr['left'] == $arr['right'] && $arr['top'] == $arr['left'] ) {
					$storage[ $tag ] = "$tag:" . $arr['top'] . ';';
				}
				// Opposites are the same
				else if ( $arr['top'] == $arr['bottom'] && $arr['left'] == $arr['right'] ) {
					$storage[ $tag ] = "$tag:" . $arr['top'] . ' ' . $arr['left'] . ';';
				}
				// 3-point directional
				else if ( $arr['right'] == $arr['left'] ) {
					$storage[ $tag ] = "$tag:" . $arr['top'] . ' ' . $arr['right'] . ' ' . $arr['bottom'] . ';';
				}
				// none are the same, but can still use shorthand notation
				else {
					$storage[ $tag ] = "$tag:" . $arr['top'] . ' ' . $arr['right'] . ' ' . $arr['bottom'] . ' ' . $arr['left'] . ';';
				}
			}
			else {
				unset( $storage[ $tag ] );
			}
		}

		// Now rebuild the string replacing all instances of margin/padding if shorthand exists
		$pos = 0;
		while ( preg_match( $this->rmp, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$prop = $match[ 2 ][ 0 ];
			if ( isset( $storage[ $prop ] ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $storage[ $prop ], $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $storage[ $prop ] ) - $colon - 1;
				$storage[ $prop ] = '';
			}
			else {
				$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Combines multiple border properties into single definition
	 *
	 * @param (string) val: CSS Selector Properties
	 */
	private function combineBorderDefinitions( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->rborder, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			if ( ! isset( $storage[ $match[ 2 ][ 0 ] ] ) ) {
				$storage[ $match[ 2 ][ 0 ] ] = array( $match[ 3 ][ 0 ] => $match[ 4 ][ 0 ] );
			}

			// Override double written properties
			$storage[ $match[ 2 ][ 0 ] ][ $match[ 3 ][ 0 ] ] = $match[ 4 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// Go through each tag for possible combination
		foreach ( $storage as $tag => $arr ) {
			// All 4 have to be defined
			if ( count( $arr ) == 4 && $arr['top'] == $arr['bottom'] && $arr['left'] == $arr['right'] && $arr['top'] == $arr['right'] ) {
				$storage[ $tag ] = "$tag:" . $arr['top'] . ';';
			}
			else {
				unset( $storage[ $tag ] );
			}
		}

		// Now rebuild the string replacing all instances
		$pos = 0;
		while ( preg_match( $this->rborder, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$prop = $match[ 2 ][ 0 ];
			if ( isset( $storage[ $prop ] ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $storage[ $prop ], $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $storage[ $prop ] ) - $colon - 1;
				$storage[ $prop ] = '';
			}
			else {
				$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Combines multiple font-definitions into single definition
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineFontDefinitions( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->rfont, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$storage[ $match[ 2 ][ 0 ] . '-' . $match[ 3 ][ 0 ] ] = $match[ 4 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// Combine font-size & line-height if possible
		if ( isset( $storage['font-size'] ) && isset( $storage['line-height'] ) ) {
			$storage['size/height'] = $storage['font-size'] . '/' . $storage['line-height'];
			unset( $storage['font-size'], $storage['line-height'] );
		}

		// Setup property groupings
		$fonts = array(
			array( 'font-style', 'font-variant', 'font-weight', 'size/height', 'font-family' ),
			array( 'font-style', 'font-variant', 'font-weight', 'font-size', 'font-family' ),
			array( 'font-style', 'font-variant', 'size/height', 'font-family' ),
			array( 'font-style', 'font-variant', 'font-size', 'font-family' ),
			array( 'font-style', 'font-weight', 'size/height', 'font-family' ),
			array( 'font-style', 'font-weight', 'font-size', 'font-family' ),
			array( 'font-variant', 'font-weight', 'size/height', 'font-family' ),
			array( 'font-variant', 'font-weight', 'font-size', 'font-family' ),
			array( 'font-weight', 'size/height', 'font-family' ),
			array( 'font-weight', 'font-size', 'font-family' ),
			array( 'font-variant', 'size/height', 'font-family' ),
			array( 'font-variant', 'font-size', 'font-family' ),
			array( 'font-style', 'size/height', 'font-family' ),
			array( 'font-style', 'font-size', 'font-family' ),
			array( 'size/height', 'font-family' ),
			array( 'font-size', 'font-family' ),
		);

		// Loop through each property check and see if they can be replaced
		foreach ( $fonts as $props ) {
			if ( $replace = $this->searchDefinitions( 'font', $storage, $props ) ) {
				break;
			}
		}

		// If replacement string found, run it on all declarations
		if ( $replace ) {
			$pos = 0;
			while ( preg_match( $this->rfont, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
				if ( ! isset( $storage['line-height'] ) && stripos( $match[ 0 ][ 0 ], 'line-height') === 0 ) {
					$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
					continue;
				}
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $replace, $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $replace ) - $colon - 1;
				$replace = '';
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Combines multiple background props into single definition
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineBackgroundDefinitions( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->rbackground, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$storage[ $match[ 2 ][ 0 ] ] = $match[ 3 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// List of background props to check
		$backgrounds = array(
			// With color
			array( 'color', 'image', 'repeat', 'attachment', 'position' ),
			array( 'color', 'image', 'attachment', 'position' ),
			array( 'color', 'image', 'repeat', 'position' ),
			array( 'color', 'image', 'repeat', 'attachment' ),
			array( 'color', 'image', 'repeat' ),
			array( 'color', 'image', 'attachment' ),
			array( 'color', 'image', 'position' ),
			array( 'color', 'image' ),
			// Without Color
			array( 'image', 'attachment', 'position' ),
			array( 'image', 'repeat', 'position' ),
			array( 'image', 'repeat', 'attachment' ),
			array( 'image', 'repeat' ),
			array( 'image', 'attachment' ),
			array( 'image', 'position' ),
			array( 'image' ),
			// Just Color
			array( 'color' ),
		);

		// Run background checks and get replacement str
		foreach ( $backgrounds as $props ) {
			if ( $replace = $this->searchDefinitions( 'background', $storage, $props ) ) {
				break;
			}
		}

		// If replacement string found, run it on all declarations
		if ( $replace ) {
			$pos = 0;
			while ( preg_match( $this->rbackground, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $replace, $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $replace ) - $colon - 1;
				$replace = '';
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Combines multiple list style props into single definition
	 *
	 * @param (string) val: CSS Selector Properties
	 */ 
	private function combineListProperties( $val ) {
		$storage = array();

		// Find all possible occurences and build the replacement
		$pos = 0;
		while ( preg_match( $this->rlist, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
			$storage[ $match[ 2 ][ 0 ] ] = $match[ 3 ][ 0 ];
			$pos = $match[ 0 ][ 1 ] + strlen( $match[ 0 ][ 0 ] ) - 1;
		}

		// List os list-style props to check against
		$lists = array(
			array( 'type', 'position', 'image' ),
			array( 'type', 'position' ),
			array( 'type', 'image' ),
			array( 'position', 'image' ),
			array( 'type' ),
			array( 'position' ),
			array( 'image' ),
		);

		// Run background checks and get replacement str
		foreach ( $lists as $props ) {
			if ( $replace = $this->searchDefinitions( 'list-style', $storage, $props ) ) {
				break;
			}
		}

		// If replacement string found, run it on all declarations
		if ( $replace ) {
			$pos = 0;
			while ( preg_match( $this->rlist, $val, $match, PREG_OFFSET_CAPTURE, $pos ) ) {
				$colon = strlen( $match[ 1 ][ 0 ] );
				$val = substr_replace( $val, $replace, $match[ 0 ][ 1 ] + $colon, strlen( $match[ 0 ][ 0 ] ) - $colon );
				$pos = $match[ 0 ][ 1 ] + strlen( $replace ) - $colon - 1;
				$replace = '';
			}
		}

		// Return converted val
		return $val;
	}

	/**
	 * Helper function to ensure flagged words don't get
	 * overridden
	 *
	 * @param (array|string) obj: Array/String of definitions to be checked
	 */ 
	private function checkUncombinables( $obj ) {
		if ( is_array( $obj ) ) {
			foreach ( $obj as $item ) {
				if ( preg_match( $this->rimportant, $item ) ) {
					return true;
				}
			}
			return false;
		}
		else {
			return preg_match( $this->rimportant, $obj );
		}
	}

	/**
	 * Helper function to ensure all values of search array
	 * exist within the storage array
	 *
	 * @param (string) prop: CSS Property
	 * @param (array) storage: Array of definitions found
	 * @param (array) search: Array of definitions requred
	 */ 
	private function searchDefinitions( $prop, $storage, $search ) {
		// Return if storage & search don't match
		if ( count( $storage ) != count( $search ) ) {
			return false;
		}

		$str = "$prop:";
		foreach ( $search as $value ) {
			if ( ! isset( $storage[ $value ] ) || $this->checkUncombinables( $storage[ $value ] ) ) {
				return false;
			}
			$str .= $storage[ $value ] . ' ';
		}
		return trim( $str ) . ';';
	}


	/**
	 * Access to private methods for testing
	 *
	 * @param (string) method: Method to be called
	 * @param (array) args: Array of paramters to be passed in
	 */
	public function access( $method, $args ) {
		if ( method_exists( $this, $method ) ) {
			if ( $method == 'combine' ) {
				return $this->combine( $args[ 0 ], $args[ 1 ] );
			}
			else {
				return call_user_func_array( array( $this, $method ), $args );
			}
		}
		else {
			throw new CSSCompression_Exception( "Unknown method in Color Class - " . $method );
		}
	}
};

?>
