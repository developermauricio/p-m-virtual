<?php

// If this file is called directly, abort.
if(!defined('ABSPATH')) {
	exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<?php $this->do_preview_head(); ?>
</head>
<body>
<?php
	$this->do_preview();
	$this->do_preview_footer();
?>
</body>
</html>