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
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the audio control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Audio control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.audioControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the compass', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Compass control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.compassControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the title thumbnails, if "false" the thumbnails will be with images', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Show thumbnails with titles only', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.titleThumbnails" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the thumbnails control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Thumbnails control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.thumbnailsControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the prev scene control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Prev scene control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.prevSceneControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the next scene control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Next scene control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.nextSceneControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene zoomIn control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('ZoomIn control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.zoomInControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene zoomOut control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('ZoomOut control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.zoomOutControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene lookUp control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('LookUp control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.lookUpControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene lookDown control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('LookDown control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.lookDownControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene lookLeft control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('LookLeft control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.lookLeftControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the scene lookRight control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('LookRight control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.lookRightControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the home control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Home control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.homeControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the fullscreen control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Fullscreen control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.fullscreenControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the viewer mode toggle control (normal or stereo)', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Viewer mode toggle control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.viewerModeControl" data-default="false"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the toggle interface control', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Toggle interface control', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.toogleInterfaceControl" data-default="true"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the start in the fullscreen mode', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Start in the fullscreen mode', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.startFullscreen" data-default="false"></div>
			</div>
			
			<div class="ipanorama-control">
				<div class="ipanorama-helper" title="<?php esc_html_e('Enable/disable the start in the hide interface mode', IPANORAMA_PLUGIN_NAME); ?>"></div>
				<div class="ipanorama-label"><?php esc_html_e('Start in the hide interface mode', IPANORAMA_PLUGIN_NAME); ?></div>
				<div al-toggle="modalData.config.hideInterface" data-default="false"></div>
			</div>
		</div>
		<div class="ipanorama-modal-footer">
			<div class="ipanorama-modal-btn ipanorama-modal-btn-close" al-on.click="modalData.deferred.resolve('close');"><?php esc_html_e('Close', IPANORAMA_PLUGIN_NAME); ?></div>
			<div class="ipanorama-modal-btn ipanorama-modal-btn-create" al-on.click="modalData.deferred.resolve(true);"><?php esc_html_e('OK', IPANORAMA_PLUGIN_NAME); ?></div>
		</div>
	</div>
</div>