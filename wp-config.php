<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'virtualpm' );

/** MySQL database username */
define( 'DB_USER', 'forge' );

/** MySQL database password */
define( 'DB_PASSWORD', 'yvxmSjmnWQj0d85PQDeh' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'rj1ED:D7%6Iu_]*h7mF;e=N!l:gGW&wf?[btj.}5v-F$+Wo%b3=K=;7;g3fTj[L&' );
define( 'SECURE_AUTH_KEY',  'uxdj9*I]pGlEq^y/+D>B4-&%.hxK/=o:%0O6e3LG`{Qu9]Sz4@PT^@.SU<#D`IRg' );
define( 'LOGGED_IN_KEY',    'Je6a#b^qXm([MHjw6/neF]Rb^?pD,i5Wo#?rmRtLS ThfyRc9?Q:EVP$@RiFB{7u' );
define( 'NONCE_KEY',        'pjU,P[pnuV2qP+rqsEaOD2!THQKB1&<_h|vPt3xvr!U`mx0Z%jT<9Gj.Yv,)q:w$' );
define( 'AUTH_SALT',        'sIHhz]G+F6YuahZ2+I,Ri.=oop>$c.=Rb2{/B#d.9+|~tP?j]f0$x^{PoRrC]!(Z' );
define( 'SECURE_AUTH_SALT', 'Ag%,UndW:fu[s&1hJ]X2^(5s22`r2$.V:rmllss8+rXp;mFx0jGB@Q#B,ArL:?WB' );
define( 'LOGGED_IN_SALT',   ';6,PXqN$J|3#o+mXw8ebi4YKmjB47ZEdIDmGi,5n1d|7g|3[R0Q/L7wm;uSP.7(G' );
define( 'NONCE_SALT',       'G=ml~a*h1R`2DRcqCgcx }SjB]MAC)}oK95@#uWeQPTyFWRQDC<LcB/_V[D@i@hn' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'vpm_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
