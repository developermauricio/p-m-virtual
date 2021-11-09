<?php
// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}
// Note:
// 1) we can use only one level parameters
// 2) we can use boolean, text
?>
<div id="ipanorama-modal-{{modalData.id}}" class="ipanorama-modal" tabindex="-1">
	<div class="ipanorama-modal-dialog">
		<div class="ipanorama-modal-header">
			<div class="ipanorama-modal-close" al-on.click="modalData.deferred.resolve('close');">&times;</div>
			<div class="ipanorama-modal-title"><i class="fa fa-info-circle"></i><?php esc_html_e('Setup widget settings', IPANORAMA_PLUGIN_NAME); ?></div>
		</div>
		<div class="ipanorama-modal-data">
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the title control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Title control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.titleControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the previous scene control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Previous scene control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.prevSceneControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the next scene control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Next scene control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.nextSceneControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the audio control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Audio control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.audioControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the compass control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Compass control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.compassControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the fullscreen toggle control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Fullscreen toggle control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.fullscreenControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the thumbnail previews control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Thumbnail previews control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.thumbnailControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the viewer mode toggle control (normal or stereo)', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Viewer mode toggle control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.viewerModeControl" data-default="false"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable availability of access to move the scene to the next, if possible', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Move to a next scene', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.moveToNextScene" data-default="true"></div>
			</div>
		</div>
		<div class="ipanorama-modal-footer">
			<div class="ipanorama-modal-btn ipanorama-modal-btn-close" al-on.click="modalData.deferred.resolve('close');"><?php esc_html_e('Close', IPANORAMA_PLUGIN_NAME); ?></div>
			<div class="ipanorama-modal-btn ipanorama-modal-btn-create" al-on.click="modalData.deferred.resolve(true);"><?php esc_html_e('OK', IPANORAMA_PLUGIN_NAME); ?></div>
		</div>
	</div>
</div>