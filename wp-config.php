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
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'schaefer_wedding' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
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
define( 'AUTH_KEY',         'k!372<kjVVh5f)LKc6I9WEU-$Z.n}2i0t#fWCqdyx :cZD/Qy3)b6,rG4sTNR@|@' );
define( 'SECURE_AUTH_KEY',  '!-_Yy~)KFqQ%bAhpNqeG&OLRg41=ksQT^,@@Ix^QAcFu6:D7#,w?V[*Uw>.X{PCY' );
define( 'LOGGED_IN_KEY',    '0_iiu%WUZoczhnWp?NZ`%b!)e/X*B&~vd.HvCv)A_LDh?mb`Z#Z~gLI/rssoWqj1' );
define( 'NONCE_KEY',        'qi_}xal1J.c+}naf@C:59*ApnO#[I9*#AN~HZ<SWGl1{YMDL;nfT!A5m|A|ztyzB' );
define( 'AUTH_SALT',        'ZEUz-lOh&38Nd-75s1m~}i-(e5LN$b_qNB[5vp KOXxY~3[{)POQ}3C8]JO^;~oU' );
define( 'SECURE_AUTH_SALT', 'S()A: MHr5iWV)~/YK[CvS6i@(xTOWx5k{0:N|UdgA+C&N{72vN5+=u8=Zb3):f&' );
define( 'LOGGED_IN_SALT',   'N;n$_L&!w3Is-unCF9k0w##lFe5*v_wiX6Zo}~[Fa#H/HXY2:w.6OgLRw*lMzFdM' );
define( 'NONCE_SALT',       'b9]ONI1qzs&F+m56I#7GH>RE<*X#Zsa3yt=XEW Qn;`O$<JXEKz0PIoi?&Tx}9Z ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
