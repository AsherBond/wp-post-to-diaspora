<?php

/**
 * Writes program information to a file for debugging purposes.
 */
class Logger {

	const DEBUG = 0;
	const INFO  = 1;
	const ERROR = 2;

	private static $anInstance = null;
	private $file_name;
	private $level = Logger::ERROR;

	/**
	 * Gets an instance of logger.  If one does not exist already,
	 * it is created.
	 *
	 * @return Logger
	 */
	public static function getInstance() {
		if ( self::$anInstance === null ) {
			self::$anInstance = new Logger();
		}

		return self::$anInstance;
	}

	/**
	 * Sets the full path of the file to write to.
	 *
	 * @param string $file_name The full path of the file.
	 */
	public function setFileName( $file_name ) {
		$this->file_name = $file_name;
	}

	/**
	 * Sets the amount of logging that is done.  Log messages that fall below
	 * this number are not written to the file.
	 *
	 * @param int $level The log level.
	 */
	public function setLevel( $level ) {
		$this->level = $level;
	}

	/**
	 * Writes a message to the file.
	 *
	 * @param string $message The message to log.
	 * @param int $level      The severity of the message.
	 *                        A higher the number indicates a more important message.
	 */
	public function log( $message, $level = self::DEBUG) {
		if ( ( $level >= $this->level ) && ( !empty( $this->file_name ) ) ) {
			$message = date( 'Y-m-d H:i:s', mktime() ) . ' ' . $message . "\n";

			@file_put_contents( $this->file_name, $message, FILE_APPEND );
		}
	}

}

?>
