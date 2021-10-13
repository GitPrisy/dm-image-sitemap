<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once 'ImageSitemapGenerator.php';

class ISG_CliCommand 
{
    public function __construct() {
        $this->isg = new ImageSitemapGenerator();
    }

    public function generate() {
        $this->isg->image_sitemap_create();
        WP_CLI::success( "Sitemap de imagenes generado." );
    }

    public function update() {
        $this->isg->image_sitemap_create(30);
        WP_CLI::success( "Sitemap de imagenes actualizado." );
        $this->index();
    }

    public function index() {
        $this->isg->index_sitemap();
        WP_CLI::success( "Índice del sitemap de imagenes generado correctamente." );
    }
}

WP_CLI::add_command( 'dm-image-sitemap', 'ISG_CliCommand' );