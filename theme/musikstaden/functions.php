<?php
/**
 * Musikstaden theme bootstrap.
 *
 * @package Musikstaden
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MUSIKSTADEN_VERSION', '1.0.0' );
define( 'MUSIKSTADEN_DIR', get_template_directory() );
define( 'MUSIKSTADEN_URI', get_template_directory_uri() );
define( 'MUSIKSTADEN_MAX_BANDS', 5 );

require_once MUSIKSTADEN_DIR . '/inc/helpers.php';
require_once MUSIKSTADEN_DIR . '/inc/roles.php';
require_once MUSIKSTADEN_DIR . '/inc/cpt.php';
require_once MUSIKSTADEN_DIR . '/inc/taxonomies.php';
require_once MUSIKSTADEN_DIR . '/inc/acf-fields.php';
require_once MUSIKSTADEN_DIR . '/inc/band-membership.php';
require_once MUSIKSTADEN_DIR . '/inc/similar-artists.php';
require_once MUSIKSTADEN_DIR . '/inc/applications.php';
require_once MUSIKSTADEN_DIR . '/inc/invites.php';
require_once MUSIKSTADEN_DIR . '/inc/auth.php';
require_once MUSIKSTADEN_DIR . '/inc/i18n.php';
require_once MUSIKSTADEN_DIR . '/inc/seed.php';
require_once MUSIKSTADEN_DIR . '/inc/admin.php';
require_once MUSIKSTADEN_DIR . '/inc/setup.php';
