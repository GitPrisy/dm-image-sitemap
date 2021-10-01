<?php

class ImageSitemapGenerator 
{

    public function image_sitemap_create() {
        global $wpdb;

        
        $query_images_args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            // 'date_query' => array(
            //     array(
            //         'after' => '-30 days'
            //     )
            // ),
            'posts_per_page' => -1,
        );
        
        // $query_posts_args = array(
        //     'post_type'      => array('noticia', 'post', 'consejos', 'como', 'dgt', 'esenciales', 'listas', 'que-es', 'reportajes', 'coche', 'marcas', 'video', 'foto-a-foto', 'altas-prestaciones', 'breve', 'tecnologia', 'movilidad', 'normativa'),
        //     'post_status'    => 'published',
        //     // 'date_query' => array(
        //     //     array(
        //     //         'after' => '-30 days'
        //     //     )
        //     // ),
        //     'posts_per_page' => 100,
        // );
        // $query_posts = new WP_Query( $query_posts_args );
        // print_r(get_post_gallery("412272", false));
        // print_r(get_post_gallery("412272"));die;

        // get_post($query_posts->posts[0]->gallery)
        $query_images = new WP_Query( $query_images_args );


        $images = [];
        foreach ( $query_images->posts as $image ) {
            $image_file = $image->guid;
            $title = $image->post_title;
            $caption = $image->post_excerpt;
            $parent_url = get_permalink($image->post_parent);

            $time = strtotime( $image->post_date );
            $year = date( 'Y', $time );
            $month = date( 'm', $time );
            $year_month = "$year-$month";

            if ( strpos($parent_url, '?post') ) {
                continue;
            }

            if ( !$image_file || !$title || !$caption || !$parent_url ) {
                continue;
            }

            $images[] = ['image_file' => $image_file, 'title' => $title, 'caption' => $caption, 'parent_url' => $parent_url, 'year_month' => $year_month];
        }

        $old_url = '';
        $xml = '';
        $old_year_month = '';
        $change_month = false;
        foreach ( $images as $image ) {
            if ( $old_year_month == '' ) {
                $old_year_month = $image['year_month'];
                // $xml .= '<year-month>'. $old_year_month .'</year-month>';
            }
            if ( $old_year_month != $image['year_month'] ) {
                $old_year_month = $image['year_month'];
                $change_month = true;
                // $xml .= '<year-month>'. $old_year_month .'</year-month>';
            }
            if ( $change_month ) {
                $xml .= '</url>';
                $this->write_out_sitemap($xml, $old_year_month);
                $change_month = false;
                $old_url = '';
                $xml = '';
            }

            if ( $old_url == '' ) {
                $xml .= '
                <url>
                    <loc>'. $image['parent_url'] .'</loc>';
                $old_url = $image['parent_url'];
            }

            if ( $image['parent_url'] == $old_url ) {
                $xml .= '
                    <image:image>
                        <image:loc>'. $image['image_file'] .'</image:loc>
                        <image:caption>'. $image['caption'] .'</image:caption>
                        <image:title>' . $image['title'] .'</image:title>
                    </image:image>';
                $old_url = $image['parent_url'];
                continue;
            }

            if ( $image['parent_url'] != $old_url ) {
                $xml .= '
                </url>
                <url>
                    <loc>'. $image['parent_url'] .'</loc>
                    <image:image>
                        <image:loc>'. $image['image_file'] .'</image:loc>
                        <image:caption>'. $image['caption'] .'</image:caption>
                        <image:title>' . $image['title'] .'</image:title>
                    </image:image>';
                $old_url = $image['parent_url'];
                continue;
            }
        }
        // print_r($xml);die;
    }

    private function index_sitemap() {
        $xml = '
        <?xml version="1.0" encoding="UTF-8"?>
            <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $files = scandir($_SERVER["DOCUMENT_ROOT"].'/sitemaps-images/');
        foreach ( $files as $file ) {
            if ( strpos($file, 'sitemap') === false ) continue;
            $xml .= '
            <sitemap>
                <loc>' .get_site_url(). '/sitemaps-images/' .$file. '</loc>
            </sitemap>
            ';
            // }
        }
        $xml .= '</sitemapindex>';
        $this->write_out_sitemap($xml, 'index', false);
        // print_r($files);
        die;
    }

    private function write_out_sitemap($xml, $sitemap_name, $dir = true) {
        if ( $dir ) $image_sitemap_url = $_SERVER["DOCUMENT_ROOT"].'/sitemaps-images/sitemap-images-'.$sitemap_name.'.html';
        if ( !$dir ) $image_sitemap_url = $_SERVER["DOCUMENT_ROOT"].'/sitemap-images-'.$sitemap_name.'.html';
        
        if($this->is_file_writable($_SERVER["DOCUMENT_ROOT"]) || $this->is_file_writable($image_sitemap_url)) {
                if(file_put_contents($image_sitemap_url, $xml)) {
                        return;
                }
        }

        return -1;
    }

    
    private function ensure_rich_img_data($img_data) {
    
        $marcas = [
            'abarth', 'alfa-romeo', 'alpine', 'aston-martin', 'audi', 'bentley', 'bmw', 'bugati', 'citroen', 'cupra', 'dacia', 'dfsk', 'ds', 'ferrari', 'fiat', 'ford', 'hispano-suiza', 'honda', 'hyundai', 'jaguar', 'jeep', 'kia', 'koenigsegg', 'lamorghini', 'land-rover', 'lexus', 'lotus', 'maserati', 'mazda', 'mclaren', 'mercedes-benz', 'mg', 'mini', 'mitsubishi', 'nissan', 'opel', 'pagani', 'peugeot', 'polestar', 'porsche', 'renault', 'rolls-royce', 'seat', 'skoda','smart', 'ssangyong', 'subaru', 'suzuki', 'tesla', 'toyota', 'volkswagen', 'volvo'
        ];
    
        foreach ($img_data as $data) {
            $rich_data = false;
            foreach ($marcas as $marca) {
                if (strpos($data, $marca)) {
                    $rich_data = true;
                    break 1;
                }
            }
    
            if(!$rich_data) {
                $img_data = "";
            }
        }
    
        return $img_data;
    }

    private function escape_xml_entities($xml) {
        return str_replace(
            array('&','<','>','\'','"'),
            array('&amp;','&lt;','&gt;','&apos;','&quot;'),
            $xml
        );
    }

    private function is_file_writable($filename) {
        if(!is_writable($filename)) {
            if(!@chmod($filename, 0666)) {
                $dirname = dirname($filename);
                if(!is_writable($dirname)) {
                    if(!@chmod($dirname, 0666)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

}