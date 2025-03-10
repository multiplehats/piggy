<?php

namespace Leat\Utils;

class Logger
{
	private $logger;
	private $source;

	public function __construct($source = 'leat')
	{
		$this->logger = wc_get_logger();
		$this->source = 'Leat ' . '[' . $source . ']';
	}

	public function debug($message, $context = [])
	{
		$this->log('debug', $message, $context);
	}

	public function info($message, $context = [])
	{
		$this->log('info', $message, $context);
	}

	public function warning($message, $context = [])
	{
		$this->log('warning', $message, $context);
	}

	public function error($message, $context = [])
	{
		$this->log('error', $message, $context);
	}

	private function log($level, $message, $context, $only_in_dev = false)
	{
		$context['source'] = $this->source;

		// Only log if not restricted to dev mode or if we are in dev mode
		$is_dev_mode = (defined('WP_DEBUG') && WP_DEBUG) && (defined('LEAT_DEV') && constant('LEAT_DEV') === true);
		if (!$only_in_dev || $is_dev_mode) {
			$this->logger->$level($message, $context);
		}

		// Add debug logging when WP_DEBUG is enabled.
		if ((defined('WP_DEBUG') && WP_DEBUG) && (!$only_in_dev || $is_dev_mode)) {
			$debug_message = sprintf(
				'[%s] [%s] %s %s',
				$this->source,
				strtoupper($level),
				$message,
				! empty($context) ? ' Context: ' . wp_json_encode($context) : ''
			);

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is a debug message.
			error_log($debug_message);
		}
	}
}
