<?php
/**
 * Theme version — single source of truth.
 *
 * Bump MUSIKSTADEN_VERSION and MUSIKSTADEN_VERSION_NAME for each release.
 * Keep style.css "Version:" in sync (WordPress reads it on the Themes screen).
 *
 * @package Musikstaden
 */

declare(strict_types=1);

define( 'MUSIKSTADEN_VERSION', '1.0.31' );
define( 'MUSIKSTADEN_VERSION_NAME', 'Booking Toggle' );

/**
 * Full version label for display, e.g. "1.0.1 — Approval Fix".
 */
function musikstaden_version_label(): string {
	return MUSIKSTADEN_VERSION . ' — ' . MUSIKSTADEN_VERSION_NAME;
}
