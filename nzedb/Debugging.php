<?php
/**
 * Show debug to CLI and log it to a file.
 */
class Debugging
{
	////////////////////// START OF USER CHANGEABLE VARS ///////////////////
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	/**
	 * Turn on/off logging of debug messages.
	 *
	 * @default true
	 *
	 * @note You must turn on the debug setting in automated.config.php, otherwise logging will not occur regardless of this setting.
	 *
	 * @var bool $debugLogging
	 */
	private $debugLogging = true;

	/**
	 * Do you want to display the debug messages to the CLI?
	 *
	 * @default true
	 *
	 * @note Debugging must be on in automated.config.php.
	 *
	 * @var bool
	 */
	private $debugCLI = true;

	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	////////////////////// END OF USER CHANGEABLE VARS /////////////////////

	/**
	 * Max log size in KiloBytes
	 *
	 * @default 512
	 *
	 * @const int
	 */
	const logFileSize = 512;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		if ($this->debugCLI) {
			$this->debugCLI = nZEDb_DEBUG;
		}
		if ($this->debugLogging) {
			$this->debugLogging = nZEDb_LOGGING;
		}

		$this->colorCLI = new ColorCLI();
		$this->newLine = ((strtolower(substr(php_uname('s'), 0, 3)) === 'win') ? "\r\n" : "\n");
	}

	/*
	 * @param string $class    The class this is coming from.
	 * @param string $method   The method this is coming from.
	 * @param string $message  The message to log.
	 * @param int    $severity How severe is this message?
	 *               1 Fatal   - The program had to stop.
	 *               2 Error   - Something went very wrong but we recovered.
	 *               3 Warning - Not an error, but something we can probably fix.
	 *               4 Notice  - Like warning but not as bad?
	 *               5 Info    - General info, like we logged in to usenet for example.
	 *               Anything else returns.
	 *
	 * @return void
	 */
	public function logDebug ($class, $method, $message, $severity)
	{
		// Check debugging or logging is on.
		if (!$this->debugLogging && !$this->debugCLI) {
			return;
		}

		// Check if the user wants to log or echo this message and format the string.
		switch ($severity) {
			case 1:
				if (nZEDb_LOGFATAL) {
					$severity = '] [FATAL]    [';
					break;
				} else {
					return;
				}
			case 2:
				if (nZEDb_LOGERROR) {
					$severity = '] [ERROR]    [';
					break;
				} else {
					return;
				}
			case 3:
				if (nZEDb_LOGWARNING) {
					$severity = '] [WARNING]  [';
					break;
				} else {
					return;
				}
			case 4:
				if (nZEDb_LOGNOTICE) {
					$severity = '] [NOTICE]   [';
					break;
				} else {
					return;
				}
			case 5:
				if (nZEDb_LOGINFO) {
					$severity = '] [INFO]     [';
					break;
				} else {
					return;
				}
			default:
				return;
		}

		// Strip \r \n , multiple spaces and trim the message.
		$message = trim(preg_replace('/\s{2,}/', ' ', str_replace(array("\n", "\r", '\r', '\n'), ' ', $message)));

		// Current time. RFC2822 style ; Thu, 21 Dec 2000 16:01:07 +0200
		$time = '[' . Date('r');

		// Create message : [Sat, 1 Mar 2014 16:01:07 +0500] [ERROR] [NNTP.doConnect() Could not connect to news.tweaknews.com (ssl) Password is wrong.]
		$data = $time . $severity . $class . '.' . $method . '() ' . $message . ']';

		// Check if we want to echo the message.
		if ($this->debugCLI) {
			echo $this->colorCLI->debug($data);
		}

		// Check if debug logging is on.
		if (!$this->debugLogging) {
			return;
		}

		// Path to folder where log files are stored..
		$path = nZEDb_RES . DS . 'logs' . DS;

		// Check if the folder exists.
		if (!is_dir($path)) {
			if (!mkdir($path)) {
// Error creating log folder.
				return;
			}
		}

		// Name of the log file.
		$fileName = 'debug.log';

		// Full path to the log file.
		$fileLocation = $path.$fileName;

		// Initiate a new log file if we don't have one.
		if (!file_exists($fileLocation)) {
			if (!$this->initiateLog($fileLocation, $time)) {
// Error creating log file.
				return;
			}
		}

		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($fileLocation);
		if ($logSize === false) {
// Error getting log size.
			return;
		} else if ($logSize >= (self::logFileSize * 1024)) {
			if (!rename($fileLocation, $path . 'debug.old.' . time())) {
// Error renaming log.
				return;
			}

			// Create a new log.
			if (!$this->initiateLog($fileLocation, $time)) {
// Error creating log file.
				return;
			}
		}

		// Append the message to the log.
		if (!file_put_contents($fileLocation, $data . $this->newLine, FILE_APPEND)) {
// Error appending message to log file.
			return;
		}
	}

	/**
	 * Initiate a log file.
	 *
	 * @param string $path The full path to the log file.
	 * @param string $time The time in RFC2822 style.
	 *
	 * @return bool
	 */
	protected function initiateLog($path, $time)
	{
		if (!file_exists($path)) {
			if (file_put_contents($path, $time . '] [INITIATE] [Initiating new log file.]' . $this->newLine)) {
				return true;
			}
		}
		return false;
	}
}