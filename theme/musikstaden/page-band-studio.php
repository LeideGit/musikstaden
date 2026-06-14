<?php
/**
 * Band Studio — frontend band create/edit.
 *
 * Template Name: Band Studio
 *
 * @package Musikstaden
 */

declare(strict_types=1);

$slug      = get_post_field( 'post_name', get_queried_object_id() );
$is_create = ( 'nytt-band' === $slug );

musikstaden_load_band_studio( $is_create );
