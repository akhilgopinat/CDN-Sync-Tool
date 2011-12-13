<?php
/**
 * Core CST class
 *
 * Class that contains all of the methods needed to create connections, push files, etc.
 *
 * @author Ollie Armstrong
 * @package CST
 * @copyright All rights reserved 2011
 * @license GNU GPLv2
 */
class Cst {
	protected $cdnConnection, $connectionType, $fileTypes;

	function __construct() {
		$this->connectionType = 'S3';
		$this->createConnection();
		add_action('admin_menu', array($this, 'createPages'));

		// Create nonce
		add_action('init', array($this, 'createNonce'));

		// Enqueue files
		add_action('admin_init', array($this, 'enqueueFiles'));

		// Files test
		$this->findFiles();
	}

	/**
	 * Initialises the connection to the CDN
	 * 
	 */
	private function createConnection() {
		if ($this->connectionType = 'S3') {
			require_once CST_DIR.'lib/api/S3.php';
			$awsAccessKey = get_option('cst-s3-accesskey');
			$awsSecretKey = get_option('cst-s3-secretkey');
			$this->cdnConnection = new S3($awsAccessKey, $awsSecretKey);
		}
	}

	/**
	 * Pushes a file to the CDN
	 * 
	 */
	private function pushFile() {
		if ($this->connectionType = 'S3') {
			// Puts a file to the bucket
			// putObjectFile(localName, bucketName, remoteName, ACL)
			$this->cdnConnection->putObjectFile('test.txt', 'ollie-armstrong-dev-test', 'test.txt', S3::ACL_PUBLIC_READ);
		}
	}

	/**
	 * Finds all the files that need syncing and add to database
	 * 
	 */
	private function findFiles() {
		global $wpdb;

		$files = $this->getDirectoryFiles(array(get_template_directory(),get_stylesheet_directory()));
		
		// Adds file to db
		foreach($files as $file) {
			$wpdb->insert(
				CST_TABLE_FILES,
				array(
					'file_dir' => $file,
					'synced' => '0'
				)
			);
		}
	}

	/**
	 * Loops through a directory checking file types
	 * 
	 * @param array directories to loop through
	 * @return array of file directories
	 */
	private function getDirectoryFiles($dirs) {
		$files = array();
		foreach ($dirs as $dir) {
			if ($handle = opendir($dir)) {
				while (false !== ($entry = readdir($handle))) {
					if (preg_match('$.(css|js|jpe?g|gif|png)$', $entry)) {
						$files[] = $dir.$entry;
					}
				}
				closedir($handle);
			}
		}
		return $files;
	}

	public function createNonce() {
		$GLOBALS['nonce'] = wp_create_nonce('cst-nonce');
	}

	/**
	 * Enqueues the files
	 * 
	 */
	public function enqueueFiles() {
		wp_enqueue_script('cst-generic-js', plugins_url('/js/cst-js.js', CST_FILE));
		wp_enqueue_style('cst-generic-style', plugins_url('/css/cst-style.css', CST_FILE));
	}

	/**
	 * Creates the admin page(s) required
	 * 
	 */
	public function createPages() {
		require_once CST_DIR.'lib/pages/Options.php';
		add_options_page('CST Options', 'CDN Sync Tool', 'manage_options', 'cst', array('CST_Page_Options', 'page'));
	}
}