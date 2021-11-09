<?php

// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}

if(!class_exists('iPanorama_App')) :

class iPanorama_App {
	private $pluginBasename = NULL;
	
	private $ajax_action_item_update = NULL;
	private $ajax_action_item_update_status = NULL;
	private $ajax_action_settings_update = NULL;
	private $ajax_action_settings_get = NULL;
	private $ajax_action_delete_data = NULL;
	private $ajax_action_modal = NULL;
	
	private $virtualtour_id = null;
	private $virtualtour_version = null;
	private $shortcodes = array();
	private $previewPageSettings = null;
	
	function __construct($pluginBasename) {
		$this->pluginBasename = $pluginBasename;
	}
	
	function run() {
		$upload_dir = wp_upload_dir();
		$plugin_url = plugin_dir_url(dirname(__FILE__));
		
		define('IPANORAMA_PLUGIN_UPLOAD_DIR', wp_normalize_path($upload_dir['basedir'] . '/' . IPANORAMA_PLUGIN_NAME));
		define('IPANORAMA_PLUGIN_UPLOAD_URL', set_url_scheme($upload_dir['baseurl'] . '/' . IPANORAMA_PLUGIN_NAME . '/'));
		
		define('IPANORAMA_PLUGIN_PLAN', 'pro');
		
		
		$user = wp_get_current_user(); //is_super_admin()
		$allowed_roles = $this->getAllowedRoles();
		if((array_intersect($allowed_roles, $user->roles) || current_user_can('manage_options')) && is_admin()) {
			$this->ajax_action_item_update = IPANORAMA_PLUGIN_NAME . '_ajax_item_update';
			$this->ajax_action_item_update_status = IPANORAMA_PLUGIN_NAME . '_ajax_item_update_status';
			$this->ajax_action_settings_update = IPANORAMA_PLUGIN_NAME . '_ajax_settings_update';
			$this->ajax_action_settings_get = IPANORAMA_PLUGIN_NAME . '_ajax_settings_get';
			$this->ajax_action_delete_data = IPANORAMA_PLUGIN_NAME . '_ajax_delete_data';
			$this->ajax_action_modal = IPANORAMA_PLUGIN_NAME . '_ajax_modal';
			
			load_plugin_textdomain(IPANORAMA_PLUGIN_NAME, false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/');
			
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_notices', array($this, 'admin_notices'));
			add_action('in_admin_header', array($this, 'in_admin_header'));
			add_action('wp_loaded', array($this, 'page_redirects'));
			
			// important, because ajax has another url
			add_action('wp_ajax_' . $this->ajax_action_item_update, array($this, 'ajax_item_update'));
			add_action('wp_ajax_' . $this->ajax_action_item_update_status, array($this, 'ajax_item_update_status'));
			add_action('wp_ajax_' . $this->ajax_action_settings_update, array($this, 'ajax_settings_update'));
			add_action('wp_ajax_' . $this->ajax_action_settings_get, array($this, 'ajax_settings_get'));
			add_action('wp_ajax_' . $this->ajax_action_delete_data, array($this, 'ajax_delete_data'));
			add_action('wp_ajax_' . $this->ajax_action_modal, array($this, 'ajax_modal'));
		} else {
			add_shortcode(IPANORAMA_SHORTCODE_NAME, array($this, 'shortcode'));
			add_filter('do_parse_request', array($this, 'do_parse_request'));
		}
	}
	
	function joinPaths() {
		$paths = array();
		
		foreach(func_get_args() as $arg) {
			if($arg !== '') {
				$paths[] = $arg;
			}
		}
		
		return preg_replace('#/+#','/',join('/', $paths));
	}
	
	function joinUrls() {
		$urls = array();
		
		foreach(func_get_args() as $arg) {
			if($arg !== '') {
				$urls[] = $arg;
			}
		}
		
		return preg_replace('/([^:])(\/{2,})/','$1/',join('/', $urls));
	}
	
	function IsNullOrEmptyString($str) {
		return(!isset($str) || trim($str)==='');
	}
	
	function filesystem_method() {
		return 'direct';
	}
	
	function request_filesystem_credentials() {
		return true;
	}
	
	function getFileSystem() {
		global $wp_filesystem;
		$result = true;
		
		if(!$wp_filesystem) {
			require_once(ABSPATH . '/wp-admin/includes/file.php');
			
			add_filter('filesystem_method', array( $this, 'filesystem_method'));
			add_filter('request_filesystem_credentials', array( $this, 'request_filesystem_credentials'));
			
			$credentials = request_filesystem_credentials(site_url(), '', true, false, null );
			
			$result = WP_Filesystem($credentials);
			
			remove_filter('filesystem_method', array( $this, 'filesystem_method'));
			remove_filter('request_filesystem_credentials', array( $this, 'request_filesystem_credentials'));
		}
		
		if($result)
			return $wp_filesystem;
		return null;
	}
	
	function getAllowedRoles() {
		$allowed_roles = array('administrator');
		
		$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
		$settings_value = get_option($settings_key);
		if($settings_value) {
			$settings = unserialize($settings_value);
			if(is_array($settings->roles)) $allowed_roles = array_merge($allowed_roles, $settings->roles);
		}
		
		return $allowed_roles;
	}
	
	function getPreviewPageSettings() {
		$obj = new stdClass();
		$obj->wpHead = false;
		$obj->wpFooter = false;
		
		$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
		$settings_value = get_option($settings_key);
		if($settings_value) {
			$settings = unserialize($settings_value);
			if(property_exists($settings, 'wpHead')) {
				$obj->wpHead = $settings->wpHead;
			}
			if(property_exists($settings, 'wpFooter')) {
				$obj->wpFooter = $settings->wpFooter;
			}
		}
		
		return $obj;
	}
	
	function getGlobalSettings() {
		$obj = new stdClass();
		$obj->styles = false;
		$obj->onLoad = null;
		
		$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
		$settings_value = get_option($settings_key);
		if($settings_value) {
			$settings = unserialize($settings_value);
			if(property_exists($settings, 'customCSS')) {
				$obj->styles = $settings->customCSS->active;
			}
			if(property_exists($settings, 'customJS')) {
				$obj->onLoad = ($settings->customJS->active ? $settings->customJS->data : null);
			}
		}
		
		return $obj;
	}
	
	function getCurrentUrl() {
		$home_path = rtrim(parse_url(home_url(), PHP_URL_PATH ), '/');
		$path = rtrim(substr(add_query_arg(array()), strlen($home_path)), '/');
		
		return ($path === '') ? '/' : $path;
	}
	
	function getLoaderGlobalsName() {
		return IPANORAMA_PLUGIN_NAME . '_globals';
	}
	
	function getLoaderGlobals($timestamp) {
		$plugin_url = plugin_dir_url(dirname(__FILE__));
		
		$globals = array(
			'plan' => IPANORAMA_PLUGIN_PLAN,
			'version' => $timestamp,
			'effects_url' => $plugin_url . 'assets/js/lib/ipanorama/ipanorama-effects.min.css',
			'fontawesome_url' => $plugin_url . 'assets/css/font-awesome.min.css',
			'theme_base_url' => $plugin_url . 'assets/js/lib/ipanorama/themes/',
			'widget_base_url' => $plugin_url . 'assets/js/lib/ipanorama/widgets/',
			'scene_transition_base_url' => $plugin_url . 'assets/js/lib/ipanorama/transitions/',
			'plugin_base_url' => $plugin_url . 'assets/js/lib/ipanorama/',
			'plugin_upload_base_url' => IPANORAMA_PLUGIN_UPLOAD_URL,
			'plugin_version' => IPANORAMA_PLUGIN_VERSION,
			'settings' => $this->getGlobalSettings(),
			'ssl' => is_ssl() // please add SSL to enable Gyroscope for the virtual tour
		);
		
		return $globals;
	}
	
	function embedLoader($in_footer, $timestamp) {
		$plugin_url = plugin_dir_url(dirname(__FILE__));
		wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-loader-js', $plugin_url . 'assets/js/loader.min.js', array('jquery'), IPANORAMA_PLUGIN_VERSION, $in_footer);
		wp_localize_script(IPANORAMA_PLUGIN_NAME . '-loader-js', $this->getLoaderGlobalsName(), $this->getLoaderGlobals($timestamp));
	}
	
	/**
	 * generate main css text
	 */
	function getMainCss($itemData, $itemId) {
		$upload_dir = wp_upload_dir();
		
		// create main css
		$main_css = '';
		$main_css .= '.ipnrm-' . $itemId . ' {' . PHP_EOL;
		
		if(!$itemData->autoWidth) {
			$main_css .= ($itemData->containerWidth ? 'width:' . $itemData->containerWidth .';' . PHP_EOL : '');
		}
		
		if(!$itemData->autoHeight) {
			$main_css .= ($itemData->containerHeight ? 'height:' . $itemData->containerHeight .';' . PHP_EOL : '');
		}
		
		$main_css .= (!$this->IsNullOrEmptyString($itemData->background->color) ? 'background-color:' . $itemData->background->color . ';' . PHP_EOL : '');
		if(!$this->IsNullOrEmptyString($itemData->background->image->url)) {
			$imageUrl = $itemData->background->image->url;
			$main_css .= 'background-image:url(' . $imageUrl . ');' . PHP_EOL;
		}
		$main_css .= ($itemData->background->size ? 'background-size:' . $itemData->background->size . ';' . PHP_EOL : '');
		$main_css .= ($itemData->background->repeat ? 'background-repeat:' . $itemData->background->repeat . ';' . PHP_EOL : '');
		$main_css .= ($itemData->background->position ? 'background-position:' . $itemData->background->position . ';' . PHP_EOL : '');
		
		$main_css .= '}' . PHP_EOL;
		
		
		$itemSelector = '.ipnrm-' . $itemId;
		
		$sceneId = 0;
		foreach($itemData->scenes as $sceneKey => $scene) {
			if(!$scene->visible) {
				continue;
			}
			
			$sceneId++;
			$sceneSelector = '[data-scene-id="' . $scene->id . '"]';
			
			$markerId = 0;
			foreach($scene->markers as $markerKey => $marker) {
				if(!$marker->visible || !$marker->view->active) {
					continue;
				}
				
				$markerId++;
				$markerSelector = '[data-marker-id="' . $marker->id . '"]';
				
				$selector = $itemSelector . ' ' . $markerSelector;
				
				$main_css .= $selector . ' .ipnrm-tag {' . PHP_EOL;
				
				if(!$marker->view->autoWidth && $marker->view->width) {
					$main_css .= 'width:' . $marker->view->width . 'px;' . PHP_EOL;
				}
				
				if(!$marker->view->autoHeight && $marker->view->height) {
					$main_css .= 'height:' . $marker->view->height . 'px;' . PHP_EOL;
				}
				
				$main_css .= (!$this->IsNullOrEmptyString($marker->view->background->color) ? 'background-color:' . $marker->view->background->color . ';' . PHP_EOL : '');
				if(!$this->IsNullOrEmptyString($marker->view->background->image->url)) {
					$main_css .= 'background-image:url(' . $marker->view->background->image->url . ');' . PHP_EOL;
				}
				$main_css .= ($marker->view->background->size ? 'background-size:' . $marker->view->background->size . ';' . PHP_EOL : '');
				$main_css .= ($marker->view->background->repeat ? 'background-repeat:' . $marker->view->background->repeat . ';' . PHP_EOL : '');
				$main_css .= ($marker->view->background->position ? 'background-position:' . $marker->view->background->position . ';' . PHP_EOL : '');
				
				if($marker->view->border->all->active) {
					$main_css .= ($marker->view->border->all->width->value ? 'border-width:' . $marker->view->border->all->width->value . $marker->view->border->all->width->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->all->style ? 'border-style:' . $marker->view->border->all->style . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->all->color ? 'border-color:' . $marker->view->border->all->color . ';' . PHP_EOL : '');
				}
				
				if($marker->view->border->top->active) {
					$main_css .= ($marker->view->border->top->width->value ? 'border-top-width:' . $marker->view->border->top->width->value . $marker->view->border->top->width->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->top->style ? 'border-top-style:' . $marker->view->border->top->style . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->top->color ? 'border-top-color:' . $marker->view->border->top->color . ';' . PHP_EOL : '');
				}
				
				if($marker->view->border->right->active) {
					$main_css .= ($marker->view->border->right->width->value ? 'border-right-width:' . $marker->view->border->right->width->value . $marker->view->border->right->width->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->right->style ? 'border-right-style:' . $marker->view->border->right->style . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->right->color ? 'border-right-color:' . $marker->view->border->right->color . ';' . PHP_EOL : '');
				}
				
				if($marker->view->border->bottom->active) {
					$main_css .= ($marker->view->border->bottom->width->value ? 'border-bottom-width:' . $marker->view->border->bottom->width->value . $marker->view->border->bottom->width->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->bottom->style ? 'border-bottom-style:' . $marker->view->border->bottom->style . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->bottom->color ? 'border-bottom-color:' . $marker->view->border->bottom->color . ';' . PHP_EOL : '');
				}
				
				if($marker->view->border->left->active) {
					$main_css .= ($marker->view->border->left->width->value ? 'border-left-width:' . $marker->view->border->left->width->value . $marker->view->border->left->width->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->left->style ? 'border-left-style:' . $marker->view->border->left->style . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->border->left->color ? 'border-left-color:' . $marker->view->border->left->color . ';' . PHP_EOL : '');
				}
				
				$borderRadius = ($marker->view->border->radius->all->value ? $marker->view->border->radius->all->value . $marker->view->border->radius->all->type : NULL);
				$main_css .= ($marker->view->border->radius->topLeft->value ? 'border-top-left-radius:' . $marker->view->border->radius->topLeft->value . $marker->view->border->radius->topLeft->type . ';' . PHP_EOL : ($borderRadius ? 'border-top-left-radius:' . $borderRadius . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->border->radius->topRight->value ? 'border-top-right-radius:' . $marker->view->border->radius->topRight->value . $marker->view->border->radius->topRight->type . ';' . PHP_EOL : ($borderRadius ? 'border-top-right-radius:' . $borderRadius . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->border->radius->bottomRight->value ? 'border-bottom-right-radius:' . $marker->view->border->radius->bottomRight->value . $marker->view->border->radius->bottomRight->type . ';' . PHP_EOL : ($borderRadius ? 'border-bottom-right-radius:' . $borderRadius . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->border->radius->bottomLeft->value ? 'border-bottom-left-radius:' . $marker->view->border->radius->bottomLeft->value . $marker->view->border->radius->bottomLeft->type . ';' . PHP_EOL : ($borderRadius ? 'border-bottom-left-radius:' . $borderRadius . ';' . PHP_EOL : ''));
				
				$main_css .= '}' . PHP_EOL;
				
				$main_css .= $selector . ' .ipnrm-lbl {' . PHP_EOL;
				
				if(!$this->IsNullOrEmptyString($marker->view->icon->name) || !$this->IsNullOrEmptyString($marker->view->icon->label)) {
					$main_css .= ($marker->view->icon->size->value ? 'font-size:' . $marker->view->icon->size->value . $marker->view->icon->size->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->icon->size->value ? 'line-height:' . $marker->view->icon->size->value . $marker->view->icon->size->type . ';' . PHP_EOL : '');
					$main_css .= ($marker->view->icon->color ? 'color:' . $marker->view->icon->color . ';' . PHP_EOL : '');
				}
				$margin = ($marker->view->icon->margin->all->value ? $marker->view->icon->margin->all->value . $marker->view->icon->margin->all->type : NULL);
				$main_css .= ($marker->view->icon->margin->top->value ? 'margin-top:' . $marker->view->icon->margin->top->value . $marker->view->icon->margin->top->type . ';' . PHP_EOL : ($margin ? 'margin-top:' . $margin . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->icon->margin->right->value ? 'margin-right:' . $marker->view->icon->margin->right->value . $marker->view->icon->margin->right->type . ';' . PHP_EOL : ($margin ? 'margin-right:' . $margin . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->icon->margin->bottom->value ? 'margin-bottom:' . $marker->view->icon->margin->bottom->value . $marker->view->icon->margin->bottom->type . ';' . PHP_EOL : ($margin ? 'margin-bottom:' . $margin . ';' . PHP_EOL : ''));
				$main_css .= ($marker->view->icon->margin->left->value ? 'margin-left:' . $marker->view->icon->margin->left->value . $marker->view->icon->margin->left->type . ';' . PHP_EOL : ($margin ? 'margin-left:' . $margin . ';' . PHP_EOL : ''));
				
				$main_css .= '}' . PHP_EOL;
			}
		}
		
		return $main_css;
	}
	
	/**
	 * Shortcode output for the plugin
	 */
	function shortcode($atts) {
		extract(shortcode_atts(array('id'=>0, 'slug'=>NULL, 'class'=>NULL, 'sceneid'=>NULL, 'width'=>NULL,'height'=>NULL), $atts));
		
		if(!$id && !$slug) {
			return '<p>' . esc_html__('Error: invalid ipanorama identifier attribute', IPANORAMA_PLUGIN_NAME) . '</p>';
		}
		
		if($width && preg_match('/^\d+$/',$width)) {
			$width = $width . 'px';
		}
		
		if($height && preg_match('/^\d+$/',$height)) {
			$height = $height . 'px';
		}
		
		global $wpdb;
		$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
		$upload_dir = wp_upload_dir();
		
		$sql = ($id ? sprintf('SELECT * FROM %1$s WHERE id=%2$d AND NOT deleted', $table, $id) : sprintf('SELECT * FROM %1$s WHERE slug="%2$s" AND NOT deleted LIMIT 0, 1', $table, $slug));
		$item = $wpdb->get_row($sql, OBJECT);
		$mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_STRING);
		
		if($item && ($item->active || (!$item->active && $mode == 'preview'))) {
			$version = strtotime(mysql2date('Y-m-d H:i:s', $item->modified));
			$itemData = unserialize($item->data);
			
			array_push($this->shortcodes, array(
				'id'      => $item->id,
				'version' => $version
			));
			
			if(sizeof($this->shortcodes) == 1) {
				$this->embedLoader(true, $version);
			}
			
			// debug
			//ob_start();
			//var_dump($value);
			//$data = ob_get_clean();
			//$fp = fopen("c:\debug.txt", "w");
			//fwrite($fp, $data);
			//fclose($fp);
			
			ob_start(); // turn on buffering
			
			echo '<!-- ipanorama begin -->' . PHP_EOL;
			
			echo '<div ';
			echo 'class="ipanorama ' . 'ipnrm-' . $item->id . ($class ? ' ' . $class : '') . '"'; // $class from shortcode
			echo 'data-json-src="'. IPANORAMA_PLUGIN_UPLOAD_URL . $item->id . '/config.json?ver=' . $version . '" ';
			echo 'data-item-id="' . $item->id . '" ';
			if($sceneid) {
				echo 'data-scene-id="' . $sceneid . '" ';
			}
			
			$inlineStyles  = "display:none;";
			if($itemData->background->inline) {
				$inlineStyles .= (!$this->IsNullOrEmptyString($itemData->background->color) ? 'background-color:' . $itemData->background->color . ';' : '');
				if(!$this->IsNullOrEmptyString($itemData->background->image->url)) {
					$imageUrl = $itemData->background->image->url;
					$inlineStyles .= 'background-image:url(' . $imageUrl . ');';
				}
				$inlineStyles .= ($itemData->background->size ? 'background-size:' . $itemData->background->size . ';' : '');
				$inlineStyles .= ($itemData->background->repeat ? 'background-repeat:' . $itemData->background->repeat . ';' : '');
				$inlineStyles .= ($itemData->background->position ? 'background-position:' . $itemData->background->position . ';' : '');
			}
			
			if($width || $height) {
				$inlineStyles .= ($width ? 'width:' . $width . ';' : '');
				$inlineStyles .= ($height ? 'height:' . $height . ';' : '');
			}
			
			echo 'style="' . $inlineStyles . '"';
			
			echo '>' . PHP_EOL;
			//=============================================
			// STORE BEGIN
			echo '<div class="ipnrm-store" style="display:none;">' . PHP_EOL;
			
			foreach($itemData->scenes as $sceneKey => $scene) {
				if(!$scene->visible) {
					continue;
				}
				
				echo '<div class="ipnrm-scene" data-scene-id="' . $scene->id . '">' . PHP_EOL;
				
				//=============================================
				// MARKERS BEGIN
				echo '<div class="ipnrm-markers-data">' . PHP_EOL;
				foreach($scene->markers as $markerKey => $marker) {
					if(!$marker->visible || !$marker->view->active) {
						continue;
					}
					
					echo '<div class="ipnrm-data" data-marker-id="' . $marker->id . '">' . PHP_EOL;
						echo '<div class="ipnrm-tag">' . PHP_EOL;
						if(!$this->IsNullOrEmptyString($marker->view->icon->name) || !$this->IsNullOrEmptyString($marker->view->icon->text)) {
							echo '<div class="ipnrm-lbl">' . PHP_EOL;
								if(!$this->IsNullOrEmptyString($marker->view->icon->name)) {
									echo '<div class="ipnrm-ico"><i class="fa ' . $marker->view->icon->name . '"></i></div>' . PHP_EOL;
								}
								if(!$this->IsNullOrEmptyString($marker->view->icon->text)) {
									echo '<div class="ipnrm-txt">' . $marker->view->icon->text . '</div>' . PHP_EOL;
								}
							echo '</div>' . PHP_EOL;
						}
						echo '</div>' . PHP_EOL;
					echo '</div>' . PHP_EOL;
				}
				echo '</div>' . PHP_EOL;
				// MARKERS END
				//=============================================
				
				//=============================================
				// TOOLTIPS BEGIN
				echo '<div class="ipnrm-tooltips-data">' . PHP_EOL;
				foreach($scene->markers as $markerKey => $marker) {
					if(!$marker->visible) {
						continue;
					}
					
					if($marker->tooltip->active && !$this->IsNullOrEmptyString($marker->tooltip->data)) {
						echo '<div class="ipnrm-data" data-marker-id="' . $marker->id . '">' . PHP_EOL;
						echo do_shortcode($marker->tooltip->data) . PHP_EOL;
						echo '</div>' . PHP_EOL;
					}
				}
				echo '</div>' . PHP_EOL;
				// TOOLTIPS END
				//=============================================
				
				//=============================================
				// POPOVERS BEGIN
				echo '<div class="ipnrm-popovers-data">' . PHP_EOL;
				foreach($scene->markers as $markerKey => $marker) {
					if(!$marker->visible) {
						continue;
					}
					
					if($marker->popover->active && !$this->IsNullOrEmptyString($marker->popover->data)) {
						echo '<div class="ipnrm-data" data-marker-id="' . $marker->id . '">' . PHP_EOL;
						echo do_shortcode($marker->popover->data) . PHP_EOL;
						echo '</div>' . PHP_EOL;
					}
				}
				echo '</div>' . PHP_EOL;
				// POPOVERS END
				//=============================================
				
				echo '</div>' . PHP_EOL; // end of a scene
			}
			
			echo '</div>' . PHP_EOL;
			// STORE END
			//=============================================
			echo '</div>' . PHP_EOL;
			echo '<!-- ipanorama end -->' . PHP_EOL;
			
			$output = ob_get_contents(); // get the buffered content into a var
			ob_end_clean(); // clean buffer
			
			return $output;
		}
		
		return '<p>' . esc_html__('Error: the ipanorama item can’t be found', IPANORAMA_PLUGIN_NAME) . '</p>';
	}
	
	/**
	* output preview header data
	*/
	function do_preview_head() {
		$plugin_url = plugin_dir_url(dirname(__FILE__));
		
		if($this->previewPageSettings->wpHead) {
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-preview-css', $plugin_url . 'assets/css/preview.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all');
			
			wp_head();
		} else if($this->previewPageSettings->wpFooter) {
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-preview-css', $plugin_url . 'assets/css/preview.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all');
		} else {
			$preview_css_src = $plugin_url . 'assets/css/preview.min.css?ver=' . IPANORAMA_PLUGIN_VERSION;
			$loader_js_src = $plugin_url . 'assets/js/loader.min.js?ver=' . IPANORAMA_PLUGIN_VERSION;
			$jquery_js_src = null;
			
			global $wp_scripts;
			foreach($wp_scripts->registered as $dependency) :
				if($dependency->handle == 'jquery-core' && $dependency->src) {
					$jquery_js_src = (substr($dependency->src,0,1)=='/' ? get_site_url() . $dependency->src : $dependency->src);
					break;
				};
			endforeach;
			
			$head = '';
			$head .= wp_sprintf('<link rel="stylesheet" type="text/css" href="%s">', $preview_css_src) . PHP_EOL;
			$head .= wp_sprintf('<script type="text/javascript" src="%s"></script>', ($jquery_js_src ? $jquery_js_src : 'http://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js')) . PHP_EOL;
			$head .= wp_sprintf('<script type="text/javascript">') . PHP_EOL;
			$head .= wp_sprintf('/* <![CDATA[ */') . PHP_EOL;
			$head .= wp_sprintf('var %s=%s;',$this->getLoaderGlobalsName(), json_encode($this->getLoaderGlobals($this->virtualtour_version))) . PHP_EOL;
			$head .= wp_sprintf('/* ]]> */') . PHP_EOL;
			$head .= wp_sprintf('</script>') . PHP_EOL;
			$head .= wp_sprintf('<script type="text/javascript" src="%s"></script>', $loader_js_src) . PHP_EOL;
			
			echo $head;
		}
	}
	
	/**
	* output preview content
	*/
	function do_preview() {
		$atts = array('id'=>$this->virtualtour_id);
		echo $this->shortcode($atts);
	}
	
	/**
	* output preview footer data
	*/
	function do_preview_footer() {
		if($this->previewPageSettings->wpFooter) {
			wp_footer();
		}
	}
	
	/**
	* Run a filter to obtain some custom url settings, compare them to the current url
	* and if a match is found the custom callback is fired, the custom view is loaded
	* and request is stopped.
	*/
	function do_parse_request($result) {
		if(current_filter() !== 'do_parse_request') {
			return $result;
		}
		
		$url =$this->getCurrentUrl();
		if(preg_match('/ipanorama\/virtualtour\/([a-z0-9_-]+)/', $url, $matches)) {
			$mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_STRING);
			
			if(is_numeric($matches[1])) {
				$virtualtour_id = $matches[1];
				$shortcode = false;
				
				if($virtualtour_id != null) {
					global $wpdb;
					$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
					
					$sql = sprintf('SELECT * FROM %1$s WHERE id=%2$d AND NOT deleted', $table, $virtualtour_id);
					$item = $wpdb->get_row($sql, OBJECT);
					
					if($item && ($item->active || (!$item->active && $mode == 'preview'))) {
						$this->virtualtour_id = $item->id;
						$this->virtualtour_version = strtotime(mysql2date('Y-m-d H:i:s', $item->modified));
						
						$shortcode = true;
					}
				}
			} else {
				$virtualtour_slug = $matches[1];
				$shortcode = false;
				
				if($virtualtour_slug != null) {
					global $wpdb;
					$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
					
					$sql = sprintf('SELECT * FROM %1$s WHERE slug="%2$s" AND NOT deleted', $table, $virtualtour_slug);
					$item = $wpdb->get_row($sql, OBJECT);
					
					if($item && ($item->active || (!$item->active && $mode == 'preview'))) {
						$this->virtualtour_id = $item->id;
						$this->virtualtour_version = strtotime(mysql2date('Y-m-d H:i:s', $item->modified));
						
						$shortcode = true;
					}
				}
			}
			
			if($shortcode) {
				$this->previewPageSettings = $this->getPreviewPageSettings();
				
				require_once(plugin_dir_path(dirname(__FILE__)) . 'includes/page-preview.php');
				exit();
			}
		}
		
		return $result;
	}
	
	/**
	 * Prepare upload directory
	 */
	function admin_notices() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if(!($page===IPANORAMA_PLUGIN_NAME ||
			 $page===IPANORAMA_PLUGIN_NAME . '_item')) {
				return;
		}
		
		$wp_filesystem = $this->getFileSystem();
		
		if($wp_filesystem == null) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p>' . esc_html__('Server error. Can\'t get access to the WordPress file system. The plugin can\'t work properly, please check the permissions.', IPANORAMA_PLUGIN_NAME) . '</p>';
			echo '</div>';
			return;
		}
		
		if(!file_exists(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
			$wp_filesystem->mkdir(IPANORAMA_PLUGIN_UPLOAD_DIR);
		}
		
		if(!file_exists(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo sprintf(esc_html__('The plugin can\'t create the "%s" directory. It\'s used to store the items data.', IPANORAMA_PLUGIN_NAME) . '<br>', '<b>' . IPANORAMA_PLUGIN_NAME . '</b>');
			echo esc_html__('Please check the permissions and owner of the WordPress upload directory.', IPANORAMA_PLUGIN_NAME) . '<br>';
			echo '</p></div>';
			return;
		}
		
		if(!wp_is_writable(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo sprintf(esc_html__('The "%s" directory is not writable, therefore the item config files cannot be saved.', IPANORAMA_PLUGIN_NAME) . '<br>', '<b>' . IPANORAMA_PLUGIN_NAME . '</b>');
			echo esc_html__('Please check the permissions and owner of the WordPress upload directory.', IPANORAMA_PLUGIN_NAME) . '<br>';
			echo '</p></div>';
			return;
		}
		
		if(!file_exists(IPANORAMA_PLUGIN_UPLOAD_DIR . '/' . 'index.php')) {
			$data = '<?php' . PHP_EOL . '// silence is golden' . PHP_EOL . '?>';
			$wp_filesystem->put_contents(IPANORAMA_PLUGIN_UPLOAD_DIR . '/' . 'index.php', $data);
		}
	}
	
	/**
	 * Fires at the beginning of the content section in an admin page
	 */
	function in_admin_header() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if(!(($page===IPANORAMA_PLUGIN_NAME) ||
			 ($page===IPANORAMA_PLUGIN_NAME . '_item') ||
			 ($page===IPANORAMA_PLUGIN_NAME . '_settings'))) {
			return;
		}
		
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		add_action('admin_notices', array($this, 'admin_notices'));
	}
	
	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	function admin_menu() {
		// add "edit_posts" if we want to give access to author, editor and contributor roles
		add_menu_page(esc_html__('iPanorama 360', IPANORAMA_PLUGIN_NAME), esc_html__('iPanorama 360', IPANORAMA_PLUGIN_NAME), 'read', IPANORAMA_PLUGIN_NAME, array( $this, 'admin_menu_page_items' ), 'dashicons-welcome-view-site');
		add_submenu_page(IPANORAMA_PLUGIN_NAME, esc_html__('iPanorama 360', IPANORAMA_PLUGIN_NAME), esc_html__('All Items', IPANORAMA_PLUGIN_NAME), 'read', IPANORAMA_PLUGIN_NAME, array( $this, 'admin_menu_page_items' ));
		add_submenu_page(IPANORAMA_PLUGIN_NAME, esc_html__('iPanorama 360', IPANORAMA_PLUGIN_NAME), esc_html__('Add New', IPANORAMA_PLUGIN_NAME), 'read', IPANORAMA_PLUGIN_NAME . '_item', array( $this, 'admin_menu_page_item' ));
		add_submenu_page(IPANORAMA_PLUGIN_NAME, esc_html__('iPanorama 360', IPANORAMA_PLUGIN_NAME), esc_html__('Settings', IPANORAMA_PLUGIN_NAME), 'manage_options', IPANORAMA_PLUGIN_NAME . '_settings', array( $this, 'admin_menu_page_settings' ));
	}
	
	/**
	 * Custom redirects
	 */
	function page_redirects() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if($page===IPANORAMA_PLUGIN_NAME) {
			$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
			if($action) {
				$url = remove_query_arg(array('action', 'id', '_wpnonce'), $_SERVER['REQUEST_URI']);
				header('Refresh:0; url="' . $url . '"', true, 303);
				//wp_redirect($url); // does not work delete and duplicate operations on XAMPP
			}
		}
	}
	
	/**
	 * Show admin menu items page
	 */
	function admin_menu_page_items() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if($page===IPANORAMA_PLUGIN_NAME) {
			$plugin_url = plugin_dir_url( dirname(__FILE__) );
			$upload_dir = wp_upload_dir();
			
			// styles
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-admin-css', $plugin_url . 'assets/css/admin.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-fontawesome-css', $plugin_url . 'assets/css/font-awesome.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			
			// scripts
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-admin-js', $plugin_url . 'assets/js/admin.min.js', array('jquery'), IPANORAMA_PLUGIN_VERSION, false );
			
			// global settings to help ajax work
			$globals = array(
				'plan' => IPANORAMA_PLUGIN_PLAN,
				'msg_pro_title' => esc_html__('Available only in the pro version', IPANORAMA_PLUGIN_NAME),
				'upload_url' => $upload_dir['baseurl'],
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( IPANORAMA_PLUGIN_NAME . '_ajax' ),
				'ajax_msg_error' => esc_html__('Server response error', IPANORAMA_PLUGIN_NAME) //Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information
			);
			
			$globals['ajax_action_update'] = $this->ajax_action_item_update_status;
			
			require_once( plugin_dir_path( dirname(__FILE__) ) . 'includes/list-table-items.php' );
			require_once( plugin_dir_path( dirname(__FILE__) ) . 'includes/page-items.php' );
			
			// set global settings
			wp_localize_script(IPANORAMA_PLUGIN_NAME . '-admin-js', IPANORAMA_PLUGIN_NAME . '_globals', $globals);
		}
	}
	
	/**
	 * Show admin menu item page
	 */
	function admin_menu_page_item() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if($page===IPANORAMA_PLUGIN_NAME . '_item') {
			$plugin_url = plugin_dir_url(dirname(__FILE__));
			$upload_dir = wp_upload_dir();
			
			// styles
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-admin-css', $plugin_url . 'assets/css/admin.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-fontawesome-css', $plugin_url . 'assets/css/font-awesome.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-ipanorama-css', $plugin_url . 'assets/js/lib/ipanorama/ipanorama.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-ipanorama-effects-css', $plugin_url . 'assets/js/lib/ipanorama/ipanorama-effects.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			
			// scripts
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-ace-js', $plugin_url . 'assets/js/lib/ace/ace.js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-gmaps-js', (is_ssl() ? 'https:' : 'http:') . '//maps.google.com/maps/api/js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-url-js', $plugin_url . 'assets/js/lib/url/url.min.js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-three-js', $plugin_url . 'assets/js/lib/ipanorama/three.min.js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-ipanorama-js', $plugin_url . 'assets/js/lib/ipanorama/ipanorama.min.js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-jquery-ipanorama-js', $plugin_url . 'assets/js/lib/ipanorama/jquery.ipanorama.min.js', array('jquery'), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-admin-js', $plugin_url . 'assets/js/admin.min.js', array('jquery'), IPANORAMA_PLUGIN_VERSION, false );
			
			wp_enqueue_media();
			
			// global settings to help ajax work
			$globals = array(
				'plan' => IPANORAMA_PLUGIN_PLAN,
				'msg_pro_title' => esc_html__('Available only in the pro version', IPANORAMA_PLUGIN_NAME),
				'msg_edit_text' => esc_html__('Edit your text here', IPANORAMA_PLUGIN_NAME),
				'msg_custom_js_error' => esc_html__('Custom js code error', IPANORAMA_PLUGIN_NAME),
				'wp_base_url' => get_site_url(),
				'upload_base_url' => $upload_dir['baseurl'],
				'plugin_base_url' => $plugin_url,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( IPANORAMA_PLUGIN_NAME . '_ajax' ),
				'ajax_msg_error' => esc_html__('Server response error', IPANORAMA_PLUGIN_NAME) //Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information
			);
			
			$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
			
			$globals['ajax_action_get'] = $this->ajax_action_settings_get;
			$globals['ajax_action_update'] = $this->ajax_action_item_update;
			$globals['ajax_action_modal'] = $this->ajax_action_modal;
			$globals['ajax_item_id'] = $id;
			$globals['settings'] = NULL;
			$globals['config'] = NULL;
			
			$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
			$settings_value = get_option($settings_key);
			if($settings_value) {
				$globals['settings'] = json_encode(unserialize($settings_value));
			}
			
			// get item data from DB
			if($id) {
				global $wpdb;
				$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
				
				$query = $wpdb->prepare('SELECT * FROM ' . $table . ' WHERE id=%s', $id);
				$item = $wpdb->get_row($query, OBJECT);
				if($item && (current_user_can('manage_options') || get_current_user_id()==$item->author)) {
					//{
					//id: null,
					//title: null,
					//active: true,
					//config: {...}
					//}
					//$globals['config'] = json_encode(unserialize($item->data)); // deprecated, because in some situations we can have double quotes that corrupts the json structure
					$globals['config'] = htmlspecialchars(json_encode(unserialize($item->data)), ENT_QUOTES, 'UTF-8');
				} else {
					exit;
				}
			} else {
				// new item
				$item = (object) array(
					'author' => get_current_user_id(),
					'editor' => get_current_user_id(),
					'created' => current_time('mysql', false),
					'modified' => current_time('mysql', false)
				);
			}
			
			require_once(plugin_dir_path( dirname(__FILE__) ) . 'includes/page-item.php' );
			
			// set global settings
			wp_localize_script(IPANORAMA_PLUGIN_NAME . '-admin-js', IPANORAMA_PLUGIN_NAME . '_globals', $globals);
		}
	}
	
	/**
	 * Show admin menu settings page
	 */
	function admin_menu_page_settings() {
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
		
		if($page===IPANORAMA_PLUGIN_NAME . '_settings') {
			$plugin_url = plugin_dir_url(dirname(__FILE__));
			
			// styles
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-admin-css', $plugin_url . 'assets/css/admin.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			wp_enqueue_style(IPANORAMA_PLUGIN_NAME . '-fontawesome-css', $plugin_url . 'assets/css/font-awesome.min.css', array(), IPANORAMA_PLUGIN_VERSION, 'all' );
			
			// scripts
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-ace-js', $plugin_url . 'assets/js/lib/ace/ace.js', array(), IPANORAMA_PLUGIN_VERSION, false );
			wp_enqueue_script(IPANORAMA_PLUGIN_NAME . '-admin-js', $plugin_url . 'assets/js/admin.min.js', array('jquery'), IPANORAMA_PLUGIN_VERSION, false );
			
			// global settings to help ajax work
			$globals = array(
				'plan' => IPANORAMA_PLUGIN_PLAN,
				'msg_pro_title' => esc_html__('Available only in the pro version', IPANORAMA_PLUGIN_NAME),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( IPANORAMA_PLUGIN_NAME . '_ajax' ),
				'ajax_msg_error' => esc_html__('Server response error', IPANORAMA_PLUGIN_NAME) //Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information
			);
			
			$globals['ajax_action_update'] = $this->ajax_action_settings_update;
			$globals['ajax_action_get'] = $this->ajax_action_settings_get;
			$globals['ajax_action_modal'] = $this->ajax_action_modal;
			$globals['ajax_action_delete_data'] = $this->ajax_action_delete_data;
			$globals['config'] = NULL;
			
			// read settings
			$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
			$settings_value = get_option($settings_key);
			if($settings_value) {
				$globals['config'] = json_encode(unserialize($settings_value));
			}
			
			require_once(plugin_dir_path( dirname(__FILE__) ) . 'includes/page-settings.php' );
			
			// set global settings
			wp_localize_script(IPANORAMA_PLUGIN_NAME . '-admin-js', IPANORAMA_PLUGIN_NAME . '_globals', $globals);
		}
	}
	
	/**
	 * Ajax update item state
	 */
	function ajax_item_update_status() {
		$error = false;
		$data = array();
		$config = filter_input(INPUT_POST, 'config', FILTER_UNSAFE_RAW);
		
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			global $wpdb;
			
			$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
			$config = json_decode($config);
			$result = false;
			
			if(isset($config->id) && isset($config->active)) {
				$query = $wpdb->prepare('SELECT * FROM ' . $table . ' WHERE id=%s', $config->id);
				$item = $wpdb->get_row($query, OBJECT );
				
				if($item && (current_user_can('manage_options') || get_current_user_id()==$item->author) ) {
					$itemData = unserialize($item->data);
					$itemData->active = $config->active;
					
					$result = $wpdb->update(
						$table,
						array(
							'active'=> $itemData->active,
							'data' => serialize($itemData)
						),
						array('id'=>$config->id));
				}
			}
			
			if($result) {
				$data['id'] = $config->id;
				$data['msg'] = esc_html__('Item updated', IPANORAMA_PLUGIN_NAME);
			} else {
				$error = true;
				$data['msg'] = esc_html__('The operation failed, can\'t update the item', IPANORAMA_PLUGIN_NAME);
			}
		} else {
			$error = true;
			$data['msg'] = esc_html__('The operation failed', IPANORAMA_PLUGIN_NAME);
		}
		
		if($error) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data);
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	/**
	 * Ajax update item data
	 */
	function ajax_item_update() {
		$error = false;
		$data = array();
		
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			global $wpdb;
			$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
			
			$inputId = filter_input(INPUT_POST, 'id', FILTER_UNSAFE_RAW);
			$type = filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW);
			$flag = true;
			
			if(IPANORAMA_PLUGIN_PLAN == 'lite') {
				$rowcount = $wpdb->get_var('SELECT COUNT(*) FROM ' . $table );
				
				if(!($rowcount == 0 || ($rowcount == 1 && $inputId))) {
					$flag = false;
					$error = true;
					$data['msg'] = esc_html__('The operation failed, you can work only with one item. To create more, buy the pro version.', IPANORAMA_PLUGIN_NAME);
				}
			}
			
			if($flag) {
				if($type == 'config') {
					$inputData = filter_input(INPUT_POST, 'data', FILTER_UNSAFE_RAW);
					$inputConfig = filter_input(INPUT_POST, 'config', FILTER_UNSAFE_RAW);
					
					$itemData = json_decode($inputData);
					$itemConfig = json_decode($inputConfig);
					
					if($inputId) {
						$result = false;
						
						$query = $wpdb->prepare('SELECT * FROM ' . $table . ' WHERE id=%s', $inputId);
						$item = $wpdb->get_row($query, OBJECT);
						if($item && (current_user_can('manage_options') || get_current_user_id()==$item->author) ) {
							$itemData->slug = sanitize_title(($itemData->slug ? $itemData->slug : $itemData->title));
							
							$result = $wpdb->update(
								$table,
								array(
									'title' => $itemData->title,
									'slug' => $itemData->slug,
									'active' => $itemData->active,
									'data' => serialize($itemData),
									'config' => serialize($itemConfig),
									//'author' => get_current_user_id(),
									'editor' => get_current_user_id(),
									//'created' => NULL,
									'modified' => current_time('mysql', false)
								),
								array('id'=>$inputId));
						}
						
						if($result) {
							$data['id'] = $inputId;
							$data['msg'] = esc_html__('The item was successfully updated', IPANORAMA_PLUGIN_NAME);
						} else {
							$error = true;
							$data['id'] = $inputId;
							$data['msg'] = esc_html__('The operation failed, can\'t update the item' . $maxp, IPANORAMA_PLUGIN_NAME);
						}
					} else {
						$itemData->slug = sanitize_title(($itemData->slug ? $itemData->slug : $itemData->title));
						
						$result = $wpdb->insert(
							$table,
							array(
								'title' => $itemData->title,
								'slug' => $itemData->slug,
								'active' => $itemData->active,
								'data' => serialize($itemData),
								'config' => serialize($itemConfig),
								'author' => get_current_user_id(),
								'editor' => get_current_user_id(),
								'created' => current_time('mysql', false),
								'modified' => current_time('mysql', false)
							));
						
						if($result) {
							$data['id'] = $inputId = $wpdb->insert_id;
							$data['msg'] = esc_html__('The item was successfully created', IPANORAMA_PLUGIN_NAME);
						} else {
							$error = true;
							$data['msg'] = esc_html__('The operation failed, can\'t create the item', IPANORAMA_PLUGIN_NAME);
						}
					}
					
					//======================================
					// [filemanager] create an external file
					if(!$error) {
						if(wp_is_writable(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
							$file_json = 'config.json';
							$file_main_css = 'main.css';
							$file_custom_css = 'custom.css';
							
							$dir_root = $this->joinPaths(IPANORAMA_PLUGIN_UPLOAD_DIR, $inputId);
							$dir_preload = $this->joinPaths($dir_root, 'preload');
							
							$url_root = $this->joinUrls(IPANORAMA_PLUGIN_UPLOAD_URL, $inputId);
							$url_preload = $this->joinUrls($url_root, 'preload');
							
							$wp_filesystem = $this->getFileSystem();
							
							if($wp_filesystem) {
								if(!$wp_filesystem->is_dir($dir_root)) {
									$wp_filesystem->mkdir($dir_root);
								}
								
								$wp_filesystem->put_contents($this->joinPaths($dir_root, $file_json), json_encode($itemConfig), FS_CHMOD_FILE);
								$wp_filesystem->put_contents($this->joinPaths($dir_root, $file_main_css), $this->getMainCss($itemData, $inputId), FS_CHMOD_FILE);
								$wp_filesystem->put_contents($this->joinPaths($dir_root, $file_custom_css), $itemData->customCSS->data, FS_CHMOD_FILE);
								
								//======================================
								// working with preload images
								if(!$wp_filesystem->is_dir($dir_preload)) {
									$wp_filesystem->mkdir($dir_preload);
								}
								
								// delete unused preload images
								$files = glob($this->joinPaths($dir_preload,'*.*'));
								foreach($files as $file) {
									$fileInfo = pathinfo($file);
									$fileName = $fileInfo['filename'];
									$fileGuid = substr($fileName, 0, 36);
									
									$doAction = true;
									foreach($itemData->scenes as $key => $scene) {
										if(strcasecmp($scene->guid, $fileGuid) == 0) {
											if($scene->type != 'cubesix' && $scene->type != 'gsv') {
												if($scene->preload->active && $scene->images->main->url) {
													$doAction = false;
												}
											}
											break;
										}
									}
									
									if($doAction) {
										$filesToDelete = glob($this->joinPaths($dir_preload, '*.*'));
										foreach($filesToDelete as $fileToDelete) {
											$fileInfo = pathinfo($fileToDelete);
											$fileName = $fileInfo['filename'];
											
											if(preg_match('/^'. $fileGuid . '/', $fileName)) {
												$wp_filesystem->delete($fileToDelete);
											}
										}
									}
								}
								
								// update json config
								for($i=0, $size=count($itemData->scenes); $i<$size; $i++) {
									$scene = $itemData->scenes[$i];
									
									if($scene->type != 'cubesix' && $scene->type != 'gsv') {
										if($scene->preload->active && $scene->images->main->url) {
											$itemConfig->scenes[$i]->imagePreload = $this->joinUrls($url_preload, $scene->guid . '-main.jpg');
										}
									}
								}
								
								$wp_filesystem->put_contents($this->joinPaths($dir_root, $file_json), json_encode($itemConfig), FS_CHMOD_FILE);
								//======================================
							} else {
								$error = true;
								$data['msg'] = esc_html__('Server error. Can\'t get access to the WordPress file system. The plugin can\'t work properly, please check the permissions.', IPANORAMA_PLUGIN_NAME);
							}
						} else {
							$error = true;
							$data['msg'] = esc_html__('Server error. The upload directory is not writable.', IPANORAMA_PLUGIN_NAME);
						}
					}
					//======================================
				} else if($type == 'preloadimage') {
					$inputScene = filter_input(INPUT_POST, 'scene', FILTER_UNSAFE_RAW);
					$inputImage = filter_input(INPUT_POST, 'image', FILTER_UNSAFE_RAW);
					$inputImage = substr($inputImage, strpos($inputImage, ',') + 1);
					
					$scene = json_decode($inputScene);
					$image = base64_decode($inputImage);
					
					if(wp_is_writable(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
						$wp_filesystem = $this->getFileSystem();
						
						$dir_root = $this->joinPaths(IPANORAMA_PLUGIN_UPLOAD_DIR, $inputId);
						$dir_preload = $this->joinPaths($dir_root, 'preload');
						
						if($wp_filesystem) {
							if(!$wp_filesystem->is_dir($dir_root)) {
								$wp_filesystem->mkdir($dir_root);
							}
							
							if(!$wp_filesystem->is_dir($dir_preload)) {
								$wp_filesystem->mkdir($dir_preload);
							}
							
							if($scene->type != 'cubesix' && $scene->type != 'gsv') {
								$wp_filesystem->put_contents($this->joinPaths($dir_preload, $scene->guid . '-main.jpg'), $image, FS_CHMOD_FILE);
							}
							
							$data['msg'] = esc_html__('The preload images were successfully created', IPANORAMA_PLUGIN_NAME);
						} else {
							$error = true;
							$data['msg'] = esc_html__('Server error. Can\'t get access to the WordPress file system. The plugin can\'t work properly, please check the permissions.', IPANORAMA_PLUGIN_NAME);
						}
					} else {
						$error = true;
						$data['msg'] = esc_html__('Server error. The upload directory is not writable.', IPANORAMA_PLUGIN_NAME);
					}
				}
			}
		} else {
			$error = true;
			$data['msg'] = esc_html__('The operation failed', IPANORAMA_PLUGIN_NAME);
		}
		
		if($error) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data);
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	/**
	 * Ajax update settings data
	 */
	function ajax_settings_update() {
		$error = false;
		$data = array();
		
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			$inputConfig = filter_input(INPUT_POST, 'config', FILTER_UNSAFE_RAW);
			$itemConfig = json_decode($inputConfig);
			
			$settings_key = IPANORAMA_PLUGIN_NAME . '_settings';
			$settings_value = serialize($itemConfig);
			$result = false;
			
			if(get_option($settings_key) == false) {
				$deprecated = null;
				$autoload = 'no';
				$result = add_option($settings_key, $settings_value, $deprecated, $autoload);
			} else {
				$old_settings_value = get_option($settings_key);
				if($old_settings_value === $settings_value) {
					$result = true;
				} else {
					$result = update_option($settings_key, $settings_value);
				}
			}
			
			if($result) {
				$data['msg'] = esc_html__('The settings were successfully updated', IPANORAMA_PLUGIN_NAME);
			} else {
				$error = true;
				$data['msg'] = esc_html__('The operation failed, can\'t update settings', IPANORAMA_PLUGIN_NAME);
			}
			
			//======================================
			// [filemanager] create an external file
			if($result) {
				if(wp_is_writable(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
					$file_global_css = 'global.css';
					
					$dir_root = IPANORAMA_PLUGIN_UPLOAD_DIR;
					$url_root = IPANORAMA_PLUGIN_UPLOAD_URL;
					
					$wp_filesystem = $this->getFileSystem();
					
					if($wp_filesystem) {
						if(!$wp_filesystem->is_dir($dir_root)) {
							$wp_filesystem->mkdir($dir_root);
						}
						
						$wp_filesystem->put_contents($this->joinPaths($dir_root, $file_global_css), $itemConfig->customCSS->data, FS_CHMOD_FILE);
					} else {
						$error = true;
						$data['msg'] = esc_html__('Server error. Can\'t get access to the WordPress file system. The plugin can\'t work properly, please check the permissions.', IPANORAMA_PLUGIN_NAME);
					}
				} else {
					$error = true;
					$data['msg'] = esc_html__('Server error. The upload directory is not writable.', IPANORAMA_PLUGIN_NAME);
				}
			}
			//======================================
		}
		
		if($error) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data);
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	/**
	 * Ajax settings get data
	 */
	function ajax_settings_get() {
		$error = false;
		$data = array();
		$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
		
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			switch($type) {
				case 'roles': {
					$data['list'] = array();
					
					$roles = wp_roles()->roles;
					foreach($roles as $key => $role) {
						if(array_key_exists('read', $role['capabilities'])) {
							array_push($data['list'], array('id' => $key, 'name' =>  translate_user_role($role['name'])));
						}
					}
				}
				break;
				case 'themes': {
					$data['list'] = array();
					
					$files = glob(plugin_dir_path( dirname(__FILE__) ) . 'assets/js/lib/ipanorama/themes/*.min.css');
					foreach($files as $file) {
						$filename = basename($file, '.min.css');
						array_push($data['list'], array('id' => $filename, 'title' => str_replace('-', ' ', $filename)));
					}
				}
				break;
				case 'widgets': {
					$data['list'] = array();
					
					$dirs = glob(plugin_dir_path(dirname(__FILE__)) . 'assets/js/lib/ipanorama/widgets/*', GLOB_ONLYDIR);
					foreach($dirs as $dir) {
						$dirname = basename($dir);
						$id = $dirname;
						$title = $dirname;
						
						$js = $this->joinPaths($dir, $dirname . '.min.js');
						$config = $this->joinPaths($dir, 'modal-config.php');
						
						if(file_exists($js)) {
							$file = fopen($js, 'r');
							while(!feof($file)) {
								$line = fgets($file);
								$pos = strpos($line, '/*!');
								if($pos === false) {
									break;
								} else {
									$line = fgets($file);
									if(!feof($file)) {
										$find = 'Widget Name:';
										$pos = strpos($line, $find);
										if($pos !== false) {
											$pos = $pos + strlen($find);
											$title = trim(substr($line, $pos));
											if($this->IsNullOrEmptyString($title)) {
												$title = $dirname;
											}
										}
									}
									break;
								}
							}
							fclose($file);
						}
						
						array_push($data['list'], array('id' => $id, 'title' => $title, 'config' => file_exists($config)));
					}
				}
				break;
				case 'transitions': {
					$data['list'] = array();
					
					$dirs = glob(plugin_dir_path(dirname(__FILE__)) . 'assets/js/lib/ipanorama/transitions/*', GLOB_ONLYDIR);
					foreach($dirs as $dir) {
						$dirname = basename($dir);
						$id = $dirname;
						$title = $dirname;
						
						$js = $this->joinPaths($dir, $dirname . '.min.js');
						$config = $this->joinPaths($dir, 'modal-config.php');
						
						if(file_exists($js)) {
							$file = fopen($js, 'r');
							while(!feof($file)) {
								$line = fgets($file);
								$pos = strpos($line, '/*!');
								if($pos === false) {
									break;
								} else {
									$line = fgets($file);
									if(!feof($file)) {
										$find = 'Transition Name:';
										$pos = strpos($line, $find);
										if($pos !== false) {
											$pos = $pos + strlen($find);
											$title = trim(substr($line, $pos));
											if($this->IsNullOrEmptyString($title)) {
												$title = $dirname;
											}
										}
									}
									break;
								}
							}
							fclose($file);
						}
						
						array_push($data['list'], array('id' => $id, 'title' => $title, 'config' => file_exists($config)));
					}
				}
				break;
				case 'editor-themes': {
					$data['list'] = array();
					
					$files = glob(plugin_dir_path( dirname(__FILE__) ) . 'assets/js/lib/ace/theme-*.js');
					foreach($files as $file) {
						$filename = str_replace('theme-','',basename($file, '.js'));
						array_push($data['list'], array('id' => $filename, 'title' => str_replace('_', ' ', $filename)));
					}
				}
				break;
				case 'icons': {
					$data['list'] = array(
						array('name' => 'fa-glass'),
						array('name' => 'fa-music'),
						array('name' => 'fa-search'),
						array('name' => 'fa-envelope-o'),
						array('name' => 'fa-heart'),
						array('name' => 'fa-star'),
						array('name' => 'fa-star-o'),
						array('name' => 'fa-user'),
						array('name' => 'fa-film'),
						array('name' => 'fa-th-large'),
						array('name' => 'fa-th'),
						array('name' => 'fa-th-list'),
						array('name' => 'fa-check'),
						array('name' => 'fa-times'),
						array('name' => 'fa-search-plus'),
						array('name' => 'fa-search-minus'),
						array('name' => 'fa-power-off'),
						array('name' => 'fa-signal'),
						array('name' => 'fa-cog'),
						array('name' => 'fa-trash-o'),
						array('name' => 'fa-home'),
						array('name' => 'fa-file-o'),
						array('name' => 'fa-clock-o'),
						array('name' => 'fa-road'),
						array('name' => 'fa-download'),
						array('name' => 'fa-arrow-circle-o-down'),
						array('name' => 'fa-arrow-circle-o-up'),
						array('name' => 'fa-inbox'),
						array('name' => 'fa-play-circle-o'),
						array('name' => 'fa-repeat'),
						array('name' => 'fa-refresh'),
						array('name' => 'fa-list-alt'),
						array('name' => 'fa-lock'),
						array('name' => 'fa-flag'),
						array('name' => 'fa-headphones'),
						array('name' => 'fa-volume-off'),
						array('name' => 'fa-volume-down'),
						array('name' => 'fa-volume-up'),
						array('name' => 'fa-qrcode'),
						array('name' => 'fa-barcode'),
						array('name' => 'fa-tag'),
						array('name' => 'fa-tags'),
						array('name' => 'fa-book'),
						array('name' => 'fa-bookmark'),
						array('name' => 'fa-print'),
						array('name' => 'fa-camera'),
						array('name' => 'fa-font'),
						array('name' => 'fa-bold'),
						array('name' => 'fa-italic'),
						array('name' => 'fa-text-height'),
						array('name' => 'fa-text-width'),
						array('name' => 'fa-align-left'),
						array('name' => 'fa-align-center'),
						array('name' => 'fa-align-right'),
						array('name' => 'fa-align-justify'),
						array('name' => 'fa-list'),
						array('name' => 'fa-dedent'),
						array('name' => 'fa-outdent'),
						array('name' => 'fa-indent'),
						array('name' => 'fa-video-camera'),
						array('name' => 'fa-image'),
						array('name' => 'fa-pencil'),
						array('name' => 'fa-map-marker'),
						array('name' => 'fa-adjust'),
						array('name' => 'fa-tint'),
						array('name' => 'fa-edit'),
						array('name' => 'fa-share-square-o'),
						array('name' => 'fa-check-square-o'),
						array('name' => 'fa-arrows'),
						array('name' => 'fa-step-backward'),
						array('name' => 'fa-fast-backward'),
						array('name' => 'fa-backward'),
						array('name' => 'fa-play'),
						array('name' => 'fa-pause'),
						array('name' => 'fa-stop'),
						array('name' => 'fa-forward'),
						array('name' => 'fa-fast-forward'),
						array('name' => 'fa-step-forward'),
						array('name' => 'fa-eject'),
						array('name' => 'fa-chevron-left'),
						array('name' => 'fa-chevron-right'),
						array('name' => 'fa-plus-circle'),
						array('name' => 'fa-minus-circle'),
						array('name' => 'fa-times-circle'),
						array('name' => 'fa-check-circle'),
						array('name' => 'fa-question-circle'),
						array('name' => 'fa-info-circle'),
						array('name' => 'fa-crosshairs'),
						array('name' => 'fa-times-circle-o'),
						array('name' => 'fa-check-circle-o'),
						array('name' => 'fa-ban'),
						array('name' => 'fa-arrow-left'),
						array('name' => 'fa-arrow-right'),
						array('name' => 'fa-arrow-up'),
						array('name' => 'fa-arrow-down'),
						array('name' => 'fa-share'),
						array('name' => 'fa-expand'),
						array('name' => 'fa-compress'),
						array('name' => 'fa-plus'),
						array('name' => 'fa-minus'),
						array('name' => 'fa-asterisk'),
						array('name' => 'fa-exclamation-circle'),
						array('name' => 'fa-gift'),
						array('name' => 'fa-leaf'),
						array('name' => 'fa-fire'),
						array('name' => 'fa-eye'),
						array('name' => 'fa-eye-slash'),
						array('name' => 'fa-warning'),
						array('name' => 'fa-plane'),
						array('name' => 'fa-calendar'),
						array('name' => 'fa-random'),
						array('name' => 'fa-comment'),
						array('name' => 'fa-magnet'),
						array('name' => 'fa-chevron-up'),
						array('name' => 'fa-chevron-down'),
						array('name' => 'fa-retweet'),
						array('name' => 'fa-shopping-cart'),
						array('name' => 'fa-folder'),
						array('name' => 'fa-folder-open'),
						array('name' => 'fa-arrows-v'),
						array('name' => 'fa-arrows-h'),
						array('name' => 'fa-bar-chart'),
						array('name' => 'fa-twitter-square'),
						array('name' => 'fa-facebook-square'),
						array('name' => 'fa-camera-retro'),
						array('name' => 'fa-key'),
						array('name' => 'fa-cogs'),
						array('name' => 'fa-comments'),
						array('name' => 'fa-thumbs-o-up'),
						array('name' => 'fa-thumbs-o-down'),
						array('name' => 'fa-star-half'),
						array('name' => 'fa-heart-o'),
						array('name' => 'fa-sign-out'),
						array('name' => 'fa-linkedin-square'),
						array('name' => 'fa-thumb-tack'),
						array('name' => 'fa-external-link'),
						array('name' => 'fa-sign-in'),
						array('name' => 'fa-trophy'),
						array('name' => 'fa-github-square'),
						array('name' => 'fa-upload'),
						array('name' => 'fa-lemon-o'),
						array('name' => 'fa-phone'),
						array('name' => 'fa-square-o'),
						array('name' => 'fa-bookmark-o'),
						array('name' => 'fa-phone-square'),
						array('name' => 'fa-twitter'),
						array('name' => 'fa-github'),
						array('name' => 'fa-unlock'),
						array('name' => 'fa-credit-card'),
						array('name' => 'fa-feed'),
						array('name' => 'fa-hdd-o'),
						array('name' => 'fa-bullhorn'),
						array('name' => 'fa-bell'),
						array('name' => 'fa-certificate'),
						array('name' => 'fa-hand-o-right'),
						array('name' => 'fa-hand-o-left'),
						array('name' => 'fa-hand-o-up'),
						array('name' => 'fa-hand-o-down'),
						array('name' => 'fa-arrow-circle-left'),
						array('name' => 'fa-arrow-circle-right'),
						array('name' => 'fa-arrow-circle-up'),
						array('name' => 'fa-arrow-circle-down'),
						array('name' => 'fa-globe'),
						array('name' => 'fa-wrench'),
						array('name' => 'fa-tasks'),
						array('name' => 'fa-filter'),
						array('name' => 'fa-briefcase'),
						array('name' => 'fa-arrows-alt'),
						array('name' => 'fa-users'),
						array('name' => 'fa-link'),
						array('name' => 'fa-cloud'),
						array('name' => 'fa-flask'),
						array('name' => 'fa-cut'),
						array('name' => 'fa-copy'),
						array('name' => 'fa-paperclip'),
						array('name' => 'fa-save'),
						array('name' => 'fa-square'),
						array('name' => 'fa-bars'),
						array('name' => 'fa-list-ul'),
						array('name' => 'fa-list-ol'),
						array('name' => 'fa-strikethrough'),
						array('name' => 'fa-underline'),
						array('name' => 'fa-table'),
						array('name' => 'fa-magic'),
						array('name' => 'fa-truck'),
						array('name' => 'fa-pinterest'),
						array('name' => 'fa-pinterest-square'),
						array('name' => 'fa-google-plus-square'),
						array('name' => 'fa-google-plus'),
						array('name' => 'fa-money'),
						array('name' => 'fa-caret-down'),
						array('name' => 'fa-caret-up'),
						array('name' => 'fa-caret-left'),
						array('name' => 'fa-caret-right'),
						array('name' => 'fa-columns'),
						array('name' => 'fa-sort'),
						array('name' => 'fa-sort-desc'),
						array('name' => 'fa-sort-asc'),
						array('name' => 'fa-envelope'),
						array('name' => 'fa-linkedin'),
						array('name' => 'fa-undo'),
						array('name' => 'fa-legal'),
						array('name' => 'fa-dashboard'),
						array('name' => 'fa-comment-o'),
						array('name' => 'fa-comments-o'),
						array('name' => 'fa-flash'),
						array('name' => 'fa-sitemap'),
						array('name' => 'fa-umbrella'),
						array('name' => 'fa-paste'),
						array('name' => 'fa-lightbulb-o'),
						array('name' => 'fa-exchange'),
						array('name' => 'fa-cloud-download'),
						array('name' => 'fa-cloud-upload'),
						array('name' => 'fa-user-md'),
						array('name' => 'fa-stethoscope'),
						array('name' => 'fa-suitcase'),
						array('name' => 'fa-bell-o'),
						array('name' => 'fa-coffee'),
						array('name' => 'fa-cutlery'),
						array('name' => 'fa-file-text-o'),
						array('name' => 'fa-building-o'),
						array('name' => 'fa-hospital-o'),
						array('name' => 'fa-ambulance'),
						array('name' => 'fa-medkit'),
						array('name' => 'fa-fighter-jet'),
						array('name' => 'fa-beer'),
						array('name' => 'fa-h-square'),
						array('name' => 'fa-plus-square'),
						array('name' => 'fa-angle-double-left'),
						array('name' => 'fa-angle-double-right'),
						array('name' => 'fa-angle-double-up'),
						array('name' => 'fa-angle-double-down'),
						array('name' => 'fa-angle-left'),
						array('name' => 'fa-angle-right'),
						array('name' => 'fa-angle-up'),
						array('name' => 'fa-angle-down'),
						array('name' => 'fa-desktop'),
						array('name' => 'fa-laptop'),
						array('name' => 'fa-tablet'),
						array('name' => 'fa-mobile'),
						array('name' => 'fa-circle-o'),
						array('name' => 'fa-quote-left'),
						array('name' => 'fa-quote-right'),
						array('name' => 'fa-spinner'),
						array('name' => 'fa-circle'),
						array('name' => 'fa-reply'),
						array('name' => 'fa-github-alt'),
						array('name' => 'fa-folder-o'),
						array('name' => 'fa-folder-open-o'),
						array('name' => 'fa-smile-o'),
						array('name' => 'fa-frown-o'),
						array('name' => 'fa-meh-o'),
						array('name' => 'fa-gamepad'),
						array('name' => 'fa-keyboard-o'),
						array('name' => 'fa-flag-o'),
						array('name' => 'fa-flag-checkered'),
						array('name' => 'fa-terminal'),
						array('name' => 'fa-code'),
						array('name' => 'fa-reply-all'),
						array('name' => 'fa-star-half-o'),
						array('name' => 'fa-location-arrow'),
						array('name' => 'fa-crop'),
						array('name' => 'fa-code-fork'),
						array('name' => 'fa-unlink'),
						array('name' => 'fa-question'),
						array('name' => 'fa-info'),
						array('name' => 'fa-exclamation'),
						array('name' => 'fa-superscript'),
						array('name' => 'fa-subscript'),
						array('name' => 'fa-eraser'),
						array('name' => 'fa-puzzle-piece'),
						array('name' => 'fa-microphone'),
						array('name' => 'fa-microphone-slash'),
						array('name' => 'fa-shield'),
						array('name' => 'fa-calendar-o'),
						array('name' => 'fa-fire-extinguisher'),
						array('name' => 'fa-rocket'),
						array('name' => 'fa-maxcdn'),
						array('name' => 'fa-chevron-circle-left'),
						array('name' => 'fa-chevron-circle-right'),
						array('name' => 'fa-chevron-circle-up'),
						array('name' => 'fa-chevron-circle-down'),
						array('name' => 'fa-html5'),
						array('name' => 'fa-css3'),
						array('name' => 'fa-anchor'),
						array('name' => 'fa-unlock-alt'),
						array('name' => 'fa-bullseye'),
						array('name' => 'fa-ellipsis-h'),
						array('name' => 'fa-ellipsis-v'),
						array('name' => 'fa-rss-square'),
						array('name' => 'fa-play-circle'),
						array('name' => 'fa-ticket'),
						array('name' => 'fa-minus-square'),
						array('name' => 'fa-minus-square-o'),
						array('name' => 'fa-level-up'),
						array('name' => 'fa-level-down'),
						array('name' => 'fa-check-square'),
						array('name' => 'fa-pencil-square'),
						array('name' => 'fa-external-link-square'),
						array('name' => 'fa-share-square'),
						array('name' => 'fa-compass'),
						array('name' => 'fa-toggle-down'),
						array('name' => 'fa-toggle-up'),
						array('name' => 'fa-toggle-right'),
						array('name' => 'fa-euro'),
						array('name' => 'fa-gbp'),
						array('name' => 'fa-dollar'),
						array('name' => 'fa-rupee'),
						array('name' => 'fa-yen'),
						array('name' => 'fa-rub'),
						array('name' => 'fa-won'),
						array('name' => 'fa-bitcoin'),
						array('name' => 'fa-file'),
						array('name' => 'fa-file-text'),
						array('name' => 'fa-sort-alpha-asc'),
						array('name' => 'fa-sort-alpha-desc'),
						array('name' => 'fa-sort-amount-asc'),
						array('name' => 'fa-sort-amount-desc'),
						array('name' => 'fa-sort-numeric-asc'),
						array('name' => 'fa-sort-numeric-desc'),
						array('name' => 'fa-thumbs-up'),
						array('name' => 'fa-thumbs-down'),
						array('name' => 'fa-youtube-square'),
						array('name' => 'fa-youtube'),
						array('name' => 'fa-xing'),
						array('name' => 'fa-xing-square'),
						array('name' => 'fa-youtube-play'),
						array('name' => 'fa-dropbox'),
						array('name' => 'fa-stack-overflow'),
						array('name' => 'fa-instagram'),
						array('name' => 'fa-flickr'),
						array('name' => 'fa-adn'),
						array('name' => 'fa-bitbucket'),
						array('name' => 'fa-bitbucket-square'),
						array('name' => 'fa-tumblr'),
						array('name' => 'fa-tumblr-square'),
						array('name' => 'fa-long-arrow-down'),
						array('name' => 'fa-long-arrow-up'),
						array('name' => 'fa-long-arrow-left'),
						array('name' => 'fa-long-arrow-right'),
						array('name' => 'fa-apple'),
						array('name' => 'fa-windows'),
						array('name' => 'fa-android'),
						array('name' => 'fa-linux'),
						array('name' => 'fa-dribbble'),
						array('name' => 'fa-skype'),
						array('name' => 'fa-foursquare'),
						array('name' => 'fa-trello'),
						array('name' => 'fa-female'),
						array('name' => 'fa-male'),
						array('name' => 'fa-gratipay'),
						array('name' => 'fa-sun-o'),
						array('name' => 'fa-moon-o'),
						array('name' => 'fa-archive'),
						array('name' => 'fa-bug'),
						array('name' => 'fa-vk'),
						array('name' => 'fa-weibo'),
						array('name' => 'fa-renren'),
						array('name' => 'fa-pagelines'),
						array('name' => 'fa-stack-exchange'),
						array('name' => 'fa-arrow-circle-o-right'),
						array('name' => 'fa-arrow-circle-o-left'),
						array('name' => 'fa-toggle-left'),
						array('name' => 'fa-dot-circle-o'),
						array('name' => 'fa-wheelchair'),
						array('name' => 'fa-vimeo-square'),
						array('name' => 'fa-try'),
						array('name' => 'fa-plus-square-o'),
						array('name' => 'fa-space-shuttle'),
						array('name' => 'fa-slack'),
						array('name' => 'fa-envelope-square'),
						array('name' => 'fa-wordpress'),
						array('name' => 'fa-openid'),
						array('name' => 'fa-bank'),
						array('name' => 'fa-mortar-board'),
						array('name' => 'fa-yahoo'),
						array('name' => 'fa-google'),
						array('name' => 'fa-reddit'),
						array('name' => 'fa-reddit-square'),
						array('name' => 'fa-stumbleupon-circle'),
						array('name' => 'fa-stumbleupon'),
						array('name' => 'fa-delicious'),
						array('name' => 'fa-digg'),
						array('name' => 'fa-pied-piper-pp'),
						array('name' => 'fa-pied-piper-alt'),
						array('name' => 'fa-drupal'),
						array('name' => 'fa-joomla'),
						array('name' => 'fa-language'),
						array('name' => 'fa-fax'),
						array('name' => 'fa-building'),
						array('name' => 'fa-child'),
						array('name' => 'fa-paw'),
						array('name' => 'fa-spoon'),
						array('name' => 'fa-cube'),
						array('name' => 'fa-cubes'),
						array('name' => 'fa-behance'),
						array('name' => 'fa-behance-square'),
						array('name' => 'fa-steam'),
						array('name' => 'fa-steam-square'),
						array('name' => 'fa-recycle'),
						array('name' => 'fa-car'),
						array('name' => 'fa-taxi'),
						array('name' => 'fa-tree'),
						array('name' => 'fa-spotify'),
						array('name' => 'fa-deviantart'),
						array('name' => 'fa-soundcloud'),
						array('name' => 'fa-database'),
						array('name' => 'fa-file-pdf-o'),
						array('name' => 'fa-file-word-o'),
						array('name' => 'fa-file-excel-o'),
						array('name' => 'fa-file-powerpoint-o'),
						array('name' => 'fa-file-image-o'),
						array('name' => 'fa-file-zip-o'),
						array('name' => 'fa-file-sound-o'),
						array('name' => 'fa-file-video-o'),
						array('name' => 'fa-file-code-o'),
						array('name' => 'fa-vine'),
						array('name' => 'fa-codepen'),
						array('name' => 'fa-jsfiddle'),
						array('name' => 'fa-support'),
						array('name' => 'fa-circle-o-notch'),
						array('name' => 'fa-resistance'),
						array('name' => 'fa-empire'),
						array('name' => 'fa-git-square'),
						array('name' => 'fa-git'),
						array('name' => 'fa-hacker-news'),
						array('name' => 'fa-tencent-weibo'),
						array('name' => 'fa-qq'),
						array('name' => 'fa-wechat'),
						array('name' => 'fa-send'),
						array('name' => 'fa-send-o'),
						array('name' => 'fa-history'),
						array('name' => 'fa-circle-thin'),
						array('name' => 'fa-header'),
						array('name' => 'fa-paragraph'),
						array('name' => 'fa-sliders'),
						array('name' => 'fa-share-alt'),
						array('name' => 'fa-share-alt-square'),
						array('name' => 'fa-bomb'),
						array('name' => 'fa-soccer-ball-o'),
						array('name' => 'fa-tty'),
						array('name' => 'fa-binoculars'),
						array('name' => 'fa-plug'),
						array('name' => 'fa-slideshare'),
						array('name' => 'fa-twitch'),
						array('name' => 'fa-yelp'),
						array('name' => 'fa-newspaper-o'),
						array('name' => 'fa-wifi'),
						array('name' => 'fa-calculator'),
						array('name' => 'fa-paypal'),
						array('name' => 'fa-google-wallet'),
						array('name' => 'fa-cc-visa'),
						array('name' => 'fa-cc-mastercard'),
						array('name' => 'fa-cc-discover'),
						array('name' => 'fa-cc-amex'),
						array('name' => 'fa-cc-paypal'),
						array('name' => 'fa-cc-stripe'),
						array('name' => 'fa-bell-slash'),
						array('name' => 'fa-bell-slash-o'),
						array('name' => 'fa-trash'),
						array('name' => 'fa-copyright'),
						array('name' => 'fa-at'),
						array('name' => 'fa-eyedropper'),
						array('name' => 'fa-paint-brush'),
						array('name' => 'fa-birthday-cake'),
						array('name' => 'fa-area-chart'),
						array('name' => 'fa-pie-chart'),
						array('name' => 'fa-line-chart'),
						array('name' => 'fa-lastfm'),
						array('name' => 'fa-lastfm-square'),
						array('name' => 'fa-toggle-off'),
						array('name' => 'fa-toggle-on'),
						array('name' => 'fa-bicycle'),
						array('name' => 'fa-bus'),
						array('name' => 'fa-ioxhost'),
						array('name' => 'fa-angellist'),
						array('name' => 'fa-cc'),
						array('name' => 'fa-shekel'),
						array('name' => 'fa-meanpath'),
						array('name' => 'fa-buysellads'),
						array('name' => 'fa-connectdevelop'),
						array('name' => 'fa-dashcube'),
						array('name' => 'fa-forumbee'),
						array('name' => 'fa-leanpub'),
						array('name' => 'fa-sellsy'),
						array('name' => 'fa-shirtsinbulk'),
						array('name' => 'fa-simplybuilt'),
						array('name' => 'fa-skyatlas'),
						array('name' => 'fa-cart-plus'),
						array('name' => 'fa-cart-arrow-down'),
						array('name' => 'fa-diamond'),
						array('name' => 'fa-ship'),
						array('name' => 'fa-user-secret'),
						array('name' => 'fa-motorcycle'),
						array('name' => 'fa-street-view'),
						array('name' => 'fa-heartbeat'),
						array('name' => 'fa-venus'),
						array('name' => 'fa-mars'),
						array('name' => 'fa-mercury'),
						array('name' => 'fa-transgender'),
						array('name' => 'fa-transgender-alt'),
						array('name' => 'fa-venus-double'),
						array('name' => 'fa-mars-double'),
						array('name' => 'fa-venus-mars'),
						array('name' => 'fa-mars-stroke'),
						array('name' => 'fa-mars-stroke-v'),
						array('name' => 'fa-mars-stroke-h'),
						array('name' => 'fa-neuter'),
						array('name' => 'fa-genderless'),
						array('name' => 'fa-facebook-official'),
						array('name' => 'fa-pinterest-p'),
						array('name' => 'fa-whatsapp'),
						array('name' => 'fa-server'),
						array('name' => 'fa-user-plus'),
						array('name' => 'fa-user-times'),
						array('name' => 'fa-bed'),
						array('name' => 'fa-viacoin'),
						array('name' => 'fa-train'),
						array('name' => 'fa-subway'),
						array('name' => 'fa-medium'),
						array('name' => 'fa-y-combinator'),
						array('name' => 'fa-optin-monster'),
						array('name' => 'fa-opencart'),
						array('name' => 'fa-expeditedssl'),
						array('name' => 'fa-battery-4'),
						array('name' => 'fa-battery-3'),
						array('name' => 'fa-battery-2'),
						array('name' => 'fa-battery-1'),
						array('name' => 'fa-battery-0'),
						array('name' => 'fa-mouse-pointer'),
						array('name' => 'fa-i-cursor'),
						array('name' => 'fa-object-group'),
						array('name' => 'fa-object-ungroup'),
						array('name' => 'fa-sticky-note'),
						array('name' => 'fa-sticky-note-o'),
						array('name' => 'fa-cc-jcb'),
						array('name' => 'fa-cc-diners-club'),
						array('name' => 'fa-clone'),
						array('name' => 'fa-balance-scale'),
						array('name' => 'fa-hourglass-o'),
						array('name' => 'fa-hourglass-1'),
						array('name' => 'fa-hourglass-2'),
						array('name' => 'fa-hourglass-3'),
						array('name' => 'fa-hourglass'),
						array('name' => 'fa-hand-grab-o'),
						array('name' => 'fa-hand-stop-o'),
						array('name' => 'fa-hand-scissors-o'),
						array('name' => 'fa-hand-lizard-o'),
						array('name' => 'fa-hand-spock-o'),
						array('name' => 'fa-hand-pointer-o'),
						array('name' => 'fa-hand-peace-o'),
						array('name' => 'fa-trademark'),
						array('name' => 'fa-registered'),
						array('name' => 'fa-creative-commons'),
						array('name' => 'fa-gg'),
						array('name' => 'fa-gg-circle'),
						array('name' => 'fa-tripadvisor'),
						array('name' => 'fa-odnoklassniki'),
						array('name' => 'fa-odnoklassniki-square'),
						array('name' => 'fa-get-pocket'),
						array('name' => 'fa-wikipedia-w'),
						array('name' => 'fa-safari'),
						array('name' => 'fa-chrome'),
						array('name' => 'fa-firefox'),
						array('name' => 'fa-opera'),
						array('name' => 'fa-internet-explorer'),
						array('name' => 'fa-tv'),
						array('name' => 'fa-contao'),
						array('name' => 'fa-500px'),
						array('name' => 'fa-amazon'),
						array('name' => 'fa-calendar-plus-o'),
						array('name' => 'fa-calendar-minus-o'),
						array('name' => 'fa-calendar-times-o'),
						array('name' => 'fa-calendar-check-o'),
						array('name' => 'fa-industry'),
						array('name' => 'fa-map-pin'),
						array('name' => 'fa-map-signs'),
						array('name' => 'fa-map-o'),
						array('name' => 'fa-map'),
						array('name' => 'fa-commenting'),
						array('name' => 'fa-commenting-o'),
						array('name' => 'fa-houzz'),
						array('name' => 'fa-vimeo'),
						array('name' => 'fa-black-tie'),
						array('name' => 'fa-fonticons'),
						array('name' => 'fa-reddit-alien'),
						array('name' => 'fa-edge'),
						array('name' => 'fa-credit-card-alt'),
						array('name' => 'fa-codiepie'),
						array('name' => 'fa-modx'),
						array('name' => 'fa-fort-awesome'),
						array('name' => 'fa-usb'),
						array('name' => 'fa-product-hunt'),
						array('name' => 'fa-mixcloud'),
						array('name' => 'fa-scribd'),
						array('name' => 'fa-pause-circle'),
						array('name' => 'fa-pause-circle-o'),
						array('name' => 'fa-stop-circle'),
						array('name' => 'fa-stop-circle-o'),
						array('name' => 'fa-shopping-bag'),
						array('name' => 'fa-shopping-basket'),
						array('name' => 'fa-hashtag'),
						array('name' => 'fa-bluetooth'),
						array('name' => 'fa-bluetooth-b'),
						array('name' => 'fa-percent'),
						array('name' => 'fa-gitlab'),
						array('name' => 'fa-wpbeginner'),
						array('name' => 'fa-wpforms'),
						array('name' => 'fa-envira'),
						array('name' => 'fa-universal-access'),
						array('name' => 'fa-wheelchair-alt'),
						array('name' => 'fa-question-circle-o'),
						array('name' => 'fa-blind'),
						array('name' => 'fa-audio-description'),
						array('name' => 'fa-volume-control-phone'),
						array('name' => 'fa-braille'),
						array('name' => 'fa-assistive-listening-systems'),
						array('name' => 'fa-asl-interpreting'),
						array('name' => 'fa-deaf'),
						array('name' => 'fa-glide'),
						array('name' => 'fa-glide-g'),
						array('name' => 'fa-signing'),
						array('name' => 'fa-low-vision'),
						array('name' => 'fa-viadeo'),
						array('name' => 'fa-viadeo-square'),
						array('name' => 'fa-snapchat'),
						array('name' => 'fa-snapchat-ghost'),
						array('name' => 'fa-snapchat-square'),
						array('name' => 'fa-pied-piper'),
						array('name' => 'fa-first-order'),
						array('name' => 'fa-yoast'),
						array('name' => 'fa-themeisle'),
						array('name' => 'fa-google-plus-circle'),
						array('name' => 'fa-font-awesome'),
						array('name' => 'fa-handshake-o'),
						array('name' => 'fa-envelope-open'),
						array('name' => 'fa-envelope-open-o'),
						array('name' => 'fa-linode'),
						array('name' => 'fa-address-book'),
						array('name' => 'fa-address-book-o'),
						array('name' => 'fa-address-card'),
						array('name' => 'fa-address-card-o'),
						array('name' => 'fa-user-circle'),
						array('name' => 'fa-user-circle-o'),
						array('name' => 'fa-user-o'),
						array('name' => 'fa-id-badge'),
						array('name' => 'fa-id-card'),
						array('name' => 'fa-id-card-o'),
						array('name' => 'fa-quora'),
						array('name' => 'fa-free-code-camp'),
						array('name' => 'fa-telegram'),
						array('name' => 'fa-thermometer-4'),
						array('name' => 'fa-thermometer-3'),
						array('name' => 'fa-thermometer-2'),
						array('name' => 'fa-thermometer-1'),
						array('name' => 'fa-thermometer-0'),
						array('name' => 'fa-shower'),
						array('name' => 'fa-bath'),
						array('name' => 'fa-podcast'),
						array('name' => 'fa-window-maximize'),
						array('name' => 'fa-window-minimize'),
						array('name' => 'fa-window-restore'),
						array('name' => 'fa-window-close'),
						array('name' => 'fa-times-rectangle-o'),
						array('name' => 'fa-window-close-o'),
						array('name' => 'fa-bandcamp'),
						array('name' => 'fa-grav'),
						array('name' => 'fa-etsy'),
						array('name' => 'fa-imdb'),
						array('name' => 'fa-ravelry'),
						array('name' => 'fa-eercast'),
						array('name' => 'fa-microchip'),
						array('name' => 'fa-snowflake-o'),
						array('name' => 'fa-superpowers'),
						array('name' => 'fa-wpexplorer'),
						array('name' => 'fa-meetup')
					);
				}
				break;
				case 'pages': {
					$data['list'] = array();
					$pages = get_pages();
					
					foreach($pages as $page) {
						$ancestors = get_post_ancestors($page->ID);
						$level = count($ancestors);
						
						array_push($data['list'], array(
							'id' => $page->ID,
							'level' => $level,
							'title' => str_repeat('- ', $level) . $page->post_title,
							'url' => esc_url(get_permalink($page->ID))
						));
					}
				}
				break;
				default: {
					$error = true;
					$data['msg'] = esc_html__('The operation failed', IPANORAMA_PLUGIN_NAME);
				}
				break;
			}
		} else {
			$error = true;
			$data['msg'] = esc_html__('The operation failed', IPANORAMA_PLUGIN_NAME);
		}
		
		if($error) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data);
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	/**
	 * Ajax delete data
	 */
	function ajax_delete_data() {
		$error = false;
		$data = array();
		$data['msg'] = esc_html__('The operation failed, can\'t delete data', IPANORAMA_PLUGIN_NAME);
		
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			global $wpdb;
			$table = $wpdb->prefix . IPANORAMA_PLUGIN_NAME;
			
			foreach($wpdb->get_results('SELECT id FROM ' . $table) as $key=>$item) {
				//======================================
				// [filemanager] delete file
				if(wp_is_writable(IPANORAMA_PLUGIN_UPLOAD_DIR)) {
					$dir_root = $this->joinPaths(IPANORAMA_PLUGIN_UPLOAD_DIR, $item->id);
					
					$wp_filesystem = $this->getFileSystem();
					
					if($wp_filesystem) {
						if($wp_filesystem->is_dir($dir_root)) {
							$wp_filesystem->rmdir($dir_root, true);
						}
					} else {
						$error = true;
						$data['msg'] = esc_html__('Server error. Can\'t get access to the WordPress file system. The plugin can\'t work properly, please check the permissions.', IPANORAMA_PLUGIN_NAME);
					}
				} else {
					$error = true;
					$data['msg'] = esc_html__('Server error. The upload directory is not writable.', IPANORAMA_PLUGIN_NAME);
				}
				//======================================
			}
			
			if(!$error) {
				$query = 'TRUNCATE TABLE ' . $table;
				$result = $wpdb->query($query);
				
				if($result) {
					$data['msg'] = esc_html__('All data was successfully deleted', IPANORAMA_PLUGIN_NAME);
				}
			}
		} else {
			$error = true;
		}
		
		if($error) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data);
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	/**
	 * Ajax settings get data
	 */
	function ajax_modal() {
		if(check_ajax_referer(IPANORAMA_PLUGIN_NAME . '_ajax', 'nonce', false)) {
			$modalName = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING);
			$modalPath = plugin_dir_path( dirname(__FILE__) ) . 'includes/modal-' . $modalName . '.php';
			
			if(strpos($modalName, 'widget-') !== false) {
				$widgetName = str_replace('widget-','',$modalName);
				$modalPath = plugin_dir_path( dirname(__FILE__) ) . 'assets/js/lib/ipanorama/widgets/' . $widgetName . '/modal-config.php';
			} else if(strpos($modalName, 'transition-') !== false) {
				$widgetName = str_replace('transition-','',$modalName);
				$modalPath = plugin_dir_path( dirname(__FILE__) ) . 'assets/js/lib/ipanorama/transitions/' . $widgetName . '/modal-config.php';
			}
			
			if(file_exists($modalPath)) {
				require_once( $modalPath );
			}
		}
		
		wp_die(); // this is required to terminate immediately and return a proper response
	}
}

endif;

?>