<?php
/*
Plugin Name: Image sitemap for Diariomotor
Description: Generate an image sitemap xml file.
Version: 0.1
Author: Iván César Fariña
Author URI: https://www.diariomotor.com
 */

if (is_admin()) {
    require_once dirname( __FILE__ ) . '/src/ImageSitemapGenerator.php';
    $isg = new ImageSitemapGenerator();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once dirname( __FILE__ ) . '/src/ISG_CliCommand.php';
}