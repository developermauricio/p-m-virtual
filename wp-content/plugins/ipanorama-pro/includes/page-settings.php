<?php

// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}

$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );

?>
<!-- /begin ipanorama app -->
<div class="ipanorama-root" id="ipanorama-app-settings" style="display:none;">
	<?php require 'page-info.php'; ?>
	<div class="ipanorama-page-header">
		<div class="ipanorama-title"><i class="xfa fa-cubes"></i><?php esc_html_e('iPanorama 360 Settings', IPANORAMA_PLUGIN_NAME); ?></div>
	</div>
	<div class="ipanorama-messages" id="ipanorama-messages">
	</div>
	<div class="ipanorama-app">
		<div class="ipanorama-loader-wrap">
			<div class="ipanorama-loader">
				<div class="ipanorama-loader-bar"></div>
				<div class="ipanorama-loader-bar"></div>
				<div class="ipanorama-loader-bar"></div>
				<div class="ipanorama-loader-bar"></div>
			</div>
		</div>
		<div class="ipanorama-wrap">
			<div class="ipanorama-workplace">
				<div class="ipanorama-main-menu">
					<div class="ipanorama-left-panel">
						<div class="ipanorama-list">
							<a class="ipanorama-item ipanorama-small ipanorama-lite" href="https://1.envato.market/zOM" al-if="appData.plan=='lite'"><?php esc_html_e('Buy Pro version', IPANORAMA_PLUGIN_NAME); ?></a>
							<a class="ipanorama-item ipanorama-small ipanorama-pro" href="#" al-if="appData.plan=='pro'"><?php esc_html_e('Pro Version', IPANORAMA_PLUGIN_NAME); ?></a>
						</div>
					</div>
					<div class="ipanorama-right-panel">
						<div class="ipanorama-list">
							<div class="ipanorama-item ipanorama-blue" al-on.click="appData.fn.saveConfig(appData);" title="<?php esc_html_e('Save config to database', IPANORAMA_PLUGIN_NAME); ?>"><?php esc_html_e('Save', IPANORAMA_PLUGIN_NAME); ?></div>
						</div>
					</div>
				</div>
				<div class="ipanorama-main-tabs ipanorama-clear-fix">
					<div class="ipanorama-tab" al-attr.class.ipanorama-active="appData.ui.tabs.general" al-on.click="appData.fn.onTab(appData, 'general')"><?php esc_html_e('General', IPANORAMA_PLUGIN_NAME); ?><div class="ipanorama-status" al-if="appData.config.active"></div></div>
					<div class="ipanorama-tab" al-attr.class.ipanorama-active="appData.ui.tabs.customCSS" al-on.click="appData.fn.onTab(appData, 'customCSS')"><?php esc_html_e('Custom CSS', IPANORAMA_PLUGIN_NAME); ?><div class="ipanorama-status" al-if="appData.config.customCSS.active"></div></div>
					<div class="ipanorama-tab" al-attr.class.ipanorama-active="appData.ui.tabs.customJS" al-on.click="appData.fn.onTab(appData, 'customJS')"><?php esc_html_e('Custom JS', IPANORAMA_PLUGIN_NAME); ?><div class="ipanorama-status" al-if="appData.config.customJS.active"></div></div>
				</div>
				<div class="ipanorama-main-data">
					<div class="ipanorama-section" al-attr.class.ipanorama-active="appData.ui.tabs.general">
						<div class="ipanorama-stage">
							<div class="ipanorama-main-panel ipanorama-main-panel-general">
								<div class="ipanorama-data ipanorama-active">
									<div class="ipanorama-control">
										<div class="ipanorama-info"><?php esc_html_e('Select the roles which should be able to access the plugin capabilities', IPANORAMA_PLUGIN_NAME); ?></div>
									</div>
									
									<div class="ipanorama-control">
										<div al-checkboxlist="appData.config.roles" data-src="appData.roles" data-predefined="administrator"></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-info"><?php esc_html_e('Preview & iframe page settings', IPANORAMA_PLUGIN_NAME); ?></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the wp_head call inside the preview page', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-label"><?php esc_html_e('Enable wp_head()', IPANORAMA_PLUGIN_NAME); ?></div>
										<div al-toggle="appData.config.wpHead"></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the wp_footer call inside the preview page', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-label"><?php esc_html_e('Enable wp_footer()', IPANORAMA_PLUGIN_NAME); ?></div>
										<div al-toggle="appData.config.wpFooter"></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-info"><?php esc_html_e('Editor settings', IPANORAMA_PLUGIN_NAME); ?></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Choose a default theme for your custom javascript editor', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-label"><?php esc_html_e('JavaScript editor theme', IPANORAMA_PLUGIN_NAME); ?></div>
										<select class="ipanorama-select" al-select="appData.config.themeJavaScript">
											<option al-option="null"><?php esc_html_e('default', IPANORAMA_PLUGIN_NAME); ?></option>
											<option al-repeat="theme in appData.themes" al-option="theme.id">{{theme.title}}</option>
										</select>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Choose a default theme for your custom css editor', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-label"><?php esc_html_e('CSS editor theme', IPANORAMA_PLUGIN_NAME); ?></div>
										<select class="ipanorama-select" al-select="appData.config.themeCSS">
											<option al-option="null"><?php esc_html_e('default', IPANORAMA_PLUGIN_NAME); ?></option>
											<option al-repeat="theme in appData.themes" al-option="theme.id">{{theme.title}}</option>
										</select>
									</div>
									
									<!--
									<div class="ipanorama-control">
										<div class="ipanorama-info"><?php esc_html_e('If you want to fully uninstall the plugin with data, you should delete all items from the database before this action', IPANORAMA_PLUGIN_NAME); ?></div>
									</div>
									
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Delete all items from the database', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-button ipanorama-red ipanorama-long" al-on.click="appData.fn.deleteAllData(appData, '<?php esc_html_e('Do you really want to delete all data?', IPANORAMA_PLUGIN_NAME); ?>');"><?php esc_html_e('Delete all items', IPANORAMA_PLUGIN_NAME); ?></div>
									</div>
									-->
								</div>
							</div>
						</div>
					</div>
					<div class="ipanorama-section" al-attr.class.ipanorama-active="appData.ui.tabs.customCSS" al-if="appData.ui.tabs.customCSS">
						<div class="ipanorama-stage">
							<div class="ipanorama-main-panel ipanorama-main-panel-general">
								<div class="ipanorama-data ipanorama-active">
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable custom styles', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-input-group">
											<div class="ipanorama-input-group-cell ipanorama-pinch">
												<div al-toggle="appData.config.customCSS.active"></div>
											</div>
											<div class="ipanorama-input-group-cell">
												<div class="ipanorama-label ipanorama-offset-top"><?php esc_html_e('Enable styles', IPANORAMA_PLUGIN_NAME); ?></div>
											</div>
										</div>
									</div>
									<div class="ipanorama-control">
										<pre id="ipanorama-notepad-css" class="ipanorama-notepad"></pre>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="ipanorama-section" al-attr.class.ipanorama-active="appData.ui.tabs.customJS" al-if="appData.ui.tabs.customJS">
						<div class="ipanorama-stage">
							<div class="ipanorama-main-panel ipanorama-main-panel-general">
								<div class="ipanorama-data ipanorama-active">
									<div class="ipanorama-control">
										<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable custom javascript code', IPANORAMA_PLUGIN_NAME); ?>"></div>
										<div class="ipanorama-input-group">
											<div class="ipanorama-input-group-cell ipanorama-pinch">
												<div al-toggle="appData.config.customJS.active"></div>
											</div>
											<div class="ipanorama-input-group-cell">
												<div class="ipanorama-label ipanorama-offset-top"><?php esc_html_e('Enable javascript code', IPANORAMA_PLUGIN_NAME); ?></div>
											</div>
										</div>
									</div>
									<div class="ipanorama-control">
										<pre id="ipanorama-notepad-js" class="ipanorama-notepad"></pre>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="ipanorama-modals" id="ipanorama-modals">
		</div>
	</div>
</div>
<!-- /end ipanorama app -->