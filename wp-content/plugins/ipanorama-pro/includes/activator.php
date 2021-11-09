<?php
/**
 * Fired during plugin activation and loading.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */

// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}

if(!class_exists('iPanorama_Activator')) :

class iPanorama_Activator {
	public function activate() {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		global $wpdb;
		
		$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
		$sql = 'CREATE TABLE ' . $table . ' (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title text COLLATE utf8_unicode_ci DEFAULT NULL,
			slug varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
			active tinyint NOT NULL DEFAULT 1,
			data longtext COLLATE utf8_unicode_ci DEFAULT NULL,
			config longtext COLLATE utf8_unicode_ci DEFAULT NULL,
			author bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			editor bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			created datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
			modified datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
			UNIQUE KEY id (id)
		);';
		dbDelta($sql);
		
		update_option(IPANORAMA_PLUGIN_NAME . '_db_version', IPANORAMA_DB_VERSION, false);
		
		$this->update_data();
		
		if(get_option(IPANORAMA_PLUGIN_NAME . '_activated') == false) {
			$this->install_data();
		}
		
		update_option(IPANORAMA_PLUGIN_NAME . '_activated', time(), false);
	}
	
	public function update_data() {
		global $wpdb;
		
		// Set default values
		$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
		
		$sql = 'UPDATE ' . $table . ' SET created=date  WHERE created=%s';
		$wpdb->query($wpdb->prepare($sql, '0000-00-00 00:00:00'));
		
		$sql = 'UPDATE ' . $table . ' SET editor=author WHERE editor=%d';
		$wpdb->query($wpdb->prepare($sql, 0));
		
		// Add support Emoji
		$sql = 'ALTER TABLE ' . $table . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$wpdb->query($sql);
		
		$sql = 'ALTER TABLE ' . $table . ' MODIFY title text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$wpdb->query($sql);
		
		$sql = 'ALTER TABLE ' . $table . ' MODIFY data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$wpdb->query($sql);
		
		$sql = 'ALTER TABLE ' . $table . ' MODIFY config longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$wpdb->query($sql);
		
		$sql = 'ALTER TABLE ' . $table . ' MODIFY slug varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$wpdb->query($sql);
		
		// Add the new column "deleted"
		$sql = 'ALTER TABLE ' . $table . ' ADD deleted tinyint NOT NULL DEFAULT 0';
		$wpdb->query($sql);
	}
	
	public function install_data() {
	}
	
	public function check_db() {
		if(get_option(IPANORAMA_PLUGIN_NAME . '_db_version') != IPANORAMA_DB_VERSION) {
			$this->activate();
		}
	}
}

endif;