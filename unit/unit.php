<?php
/**
 * CSS Compressor [VERSION] - Test Suite
 * [DATE]
 * Corey Hart @ http://www.codenothing.com
 */

// Before/After directories
// $root is borrowed from index.php
define('SPECIAL_BEFORE', $root . '/special/before/');
define('SPECIAL_AFTER', $root . '/special/after/');
define('BEFORE', $root . '/sheets/before/');
define('AFTER', $root . '/sheets/after/');


Class CSScompressionTestUnit Extends CSSCompression
{
	/**
	 * Class Variables
	 *
	 * @param (int) errors: Number of errors found
	 * @param (array) sandbox: Array containing test suite
	 * @param (string) results: Result of all tests string for table
	 */
	private $errors = 0;
	private $sandbox = array();
	private $results = '';

	/**
	 * Constructor - runs the test suite
	 *
	 * @params none
	 */ 
	public function __construct( $sandbox ) {
		parent::__construct('');

		// Reset the local class vars
		$this->sandbox = $sandbox;
		$this->errors = 0;
		$this->results = '';

		$this->setOptions();
		$this->mark( 'Start Test', 0, true );
		$this->initialTrimTest();
		$this->lineTesting();
		$this->testSemicolon();
		$this->testSelectorCombination();
		$this->testDetailCombination();

		// Full sheet tests (security checks)
		//$this->setOptions();
		//$this->testSheets();
	}

	/**
	 * Turn all options to true to test every possible function
	 *
	 * @params none
	 */ 
	private function setOptions(){
		foreach ( $this->options as $key => $value ) {
			$this->options[ $key ] = true;
		}
		$this->options[ 'readability' ] = self::READ_NONE;
	}

	/**
	 * Runs a test on the initalTrim() method of CSSC, uses
	 * a before/after system of files to do matching
	 *
	 * @params none
	 */ 
	private function initialTrimTest(){
		$this->css = file_get_contents( SPECIAL_BEFORE . 'initialTrim.css' );
		$after = file_get_contents( SPECIAL_AFTER . 'initialTrim.css' );
		$this->initialTrim();
		$this->mark( 'initialTrim.css', 'all', trim( $this->css ) == trim( $after ) );
	}

	/**
	 * Uses a test-array contain CSSC methods and various
	 * tests to run on each function. Takes special note to
	 * runSpecialCompressions() & lowercaseSelectors() methods
	 *
	 * @params none
	 */ 
	private function lineTesting(){
		foreach ( $this->sandbox as $fn => $tests ) {
			foreach ( $tests as $entry => $set ) {
				$before = $set[0];
				$after = $set[1];

				if ( $fn == 'runSpecialCompressions' ) {
					list ( $prop, $val ) = explode( ':', $before );
					list ( $prop, $val ) = $this->runSpecialCompressions( $prop, $val );
					$passed = ( "$prop:$val" == $after );
				}
				else if ( $fn == 'lowercaseSelectors' ) {
					$this->selectors = array( $before );
					$this->$fn();
					$passed = ( $this->selectors[0] == $after );
				}
				else if ( $fn == 'removeUnnecessarySemicolon' ) {
					$this->details = array( $before );
					$this->$fn();
					$passed = ( $this->details[0] == $after );
				}
				else{
					// Each function replaces all instances with compressed version of prop, so
					// add the remove multiply definitions for easier testing
					$passed = ( $this->$fn( $before ) == $after );
				}
				$this->mark( $fn, $entry, $passed );
			}
		}
	}

	/**
	 * Runs unit testing on ending semicolon removal
	 *
	 * @params none
	 */ 
	private function testSemicolon(){
		$this->details = array(
			'color:blue;',
			'color:blue;font-size:12pt;',
		);

		$after = array(
			'color:blue',
			'color:blue;font-size:12pt',
		);

		$this->removeUnnecessarySemicolon();
		$max = array_pop( array_keys( $this->details ) ) + 1;
		for ( $i = 0; $i < $max; $i++ ) {
			$this->mark( 'Unnecessary Semicolons', $i, ( $this->details[ $i ] === $after[ $i ] ) );
		}
	}

	/**
	 * Runs unit testing on the selector combination
	 *
	 * @params none
	 */ 
	private function testSelectorCombination(){
		include( SPECIAL_BEFORE . 'combineMultiplyDefinedSelectors.php' );
		$this->selectors = $selectors;
		$this->details = $details;
		$this->combineMultiplyDefinedSelectors();
		include( SPECIAL_AFTER . 'combineMultiplyDefinedSelectors.php' );
		$max = array_pop( array_keys( $this->selectors ) ) + 1;
		for ( $i = 0; $i < $max; $i++ ) {
			if ( isset( $this->selectors[ $i ] ) && isset( $this->details[ $i ] ) ) {
				$this->mark(
					'Selector Combination', 
					$i, 
					( $this->selectors[ $i ] === $selectors[ $i ] && $this->details[ $i ] === $details[ $i ] )
				);
			}
		}
	}

	/**
	 * Runs unit testing on the details combination
	 *
	 * @params none
	 */ 
	private function testDetailCombination(){
		include( SPECIAL_BEFORE . 'combineMultiplyDefinedDetails.php' );
		$this->selectors = $selectors;
		$this->details = $details;
		$this->combineMultiplyDefinedDetails();
		include( SPECIAL_AFTER . 'combineMultiplyDefinedDetails.php' );
		$max = array_pop( array_keys( $this->selectors ) ) + 1;
		for ( $i = 0; $i < $max; $i++ ) {
			if ( isset( $this->selectors[ $i ] ) && isset( $this->details[ $i ] ) ) {
				$this->mark(
					'Selector Combination',
					$i,
					( $this->selectors[ $i ] === $selectors[ $i ] && $this->details[ $i ] === $details[ $i ] )
				);
			}
		}
	}

	/**
	 * Run all test sheets through full compressor to see outcome
	 *
	 * @params none
	 */
	private function testSheets(){
		$handle = opendir( BEFORE );

		while ( ( $file = readdir( $handle ) ) !== false ) {
			if ( preg_match( "/\.css$/", $file ) ) {
				$before = trim( file_get_contents( BEFORE . $file ) );
				$after = trim( file_get_contents( AFTER . $file ) );

				echo $after . "\n\n";
				echo $this->compress( $before );
				$this->mark( $file, "full", $this->compress( $before ) === $after );
				break;
			}
		}
	}

	/**
	 * Outputs result onto table
	 *
	 * @param (string) method: CSSC Method
	 * @param (string) entry: Entry of test array
	 * @param (boolean) result: Result of test matching
	 */ 
	private function mark( $method, $entry, $result ) {
		if ( $result ) {
			echo Color::green( "Passed: ${method}[$entry]" ) . "\r\n";
		}
		else{
			$this->errors++;
			echo Color::red( "Failed: ${method}[$entry]" ) . "\r\n";
		}
	}

	/**
	 * Destructor - Displays final errors
	 *
	 * @params none
	 */ 
	public function __destruct(){
		if ( $this->errors > 0 ) {
			$final = Color::boldred( "Test Failed: " . $this->errors . " total errors." );
		}
		else {
			$final = Color::boldgreen( "All Tests Passed" );
		}
		echo "\r\n\r\n$final\r\n\r\n";
	}
};

?>