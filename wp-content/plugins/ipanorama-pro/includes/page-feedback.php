<?php

// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}

$data = '';
$data .= '<div class="ipanorama-page-feedback">' . PHP_EOL;
$data .= '<p>' . __('Your feedback and rating matters to us. If you are happy with iPanorama 360 for WordPress give us a rating.', IPANORAMA_PLUGIN_NAME) . '</p>' . PHP_EOL;
$data .= '<a class="ipanorama-rate-us" href="https://wordpress.org/plugins/ipanorama-360-virtual-tour-builder-lite/#reviews" target="_blank">' . __('Rate Us', IPANORAMA_PLUGIN_NAME) . '</a>'. PHP_EOL;
$data .= '<div class="ipanorama-page-feedback-close"><i class="xfa fa-times"></i></div>' . PHP_EOL;
$data .= '</div>' . PHP_EOL;

echo wp_kses_post($data);

?>