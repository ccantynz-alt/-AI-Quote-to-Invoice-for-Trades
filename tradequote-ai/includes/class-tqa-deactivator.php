<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Deactivator {
	public static function deactivate() {
		// Intentionally light — preserve data on deactivate; uninstall.php handles full cleanup.
	}
}
