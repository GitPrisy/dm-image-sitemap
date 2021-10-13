<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once 'ImageSitemapGenerator.php';

class ISG_CliCommand 
{
    public function __construct() {
        $this->isg = new ImageSitemapGenerator();
    }

    public function generate_all() {
        $this->isg->image_sitemap_create_new();
        WP_CLI::success( "Sitemap de imagenes generado." );
        $this->index();
    }

    public function generate_month() {
        $year = intval(date("Y"));
        $month = intval(date("m"));
        $this->isg->image_sitemap_create_month($year, $month);
        WP_CLI::success( "Sitemap de imagenes actualizado." );
        $this->index();
    }

    public function index() {
        $this->isg->index_sitemap();
        WP_CLI::success( "Índice del sitemap de imagenes generado correctamente." );
    }
}

WP_CLI::add_command( 'dm-image-sitemap', 'ISG_CliCommand' );