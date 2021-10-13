<?php

class ImageSitemapGenerator 
{
    public function image_sitemap_create($time = -1) {
        global $wpdb;

        if ($time == -1) {
            $query_images_args = array(
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'post_status'    => 'inherit',
                'posts_per_page' => $time,
            );
        } else {
            $query_images_args = array(
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'post_status'    => 'inherit',
                'date_query' => array(
                    array(
                        'after' => '-'.$time.' days'
                    )
                ),
                'posts_per_page' => -1,
            );
        }
        
        
        $query_images = new WP_Query( $query_images_args );
        $images = [];

        //get images data
        foreach ( $query_images->posts as $image ) {
            $image_file = $image->guid;
            $caption = $image->post_excerpt;
            $title = $image->post_title;
            $parent_url = get_permalink($image->post_parent);

            $time = strtotime( $image->post_date );
            $year = date( 'Y', $time );
            $month = date( 'm', $time );
            $year_month = "$year-$month";

            if ( strpos($parent_url, '?post') ) {
                continue;
            }

            //if any info is missing, exclude image
            if ( !$image_file || !$title || !$caption || !$parent_url ) {
                continue;
            }

            //include images
            $images[] = ['image_file' => $image_file, 'title' => $title, 'caption' => $caption, 'parent_url' => $parent_url, 'year_month' => $year_month];
        }


        $old_url = '';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        $old_year_month = '';
        $change_month = false;

        foreach ( $images as $image ) {
            //no y-m set
            if ( $old_year_month == '' ) {
                //set first y-m
                $old_year_month = $image['year_month'];
            }

            //if old y-m is diff change y-m
            if ( $old_year_month != $image['year_month'] ) {
                $old_year_month = $image['year_month'];
                $change_month = true;
            }

            //if change_month, end old sitemap and start new
            if ( $change_month ) {
                $xml .= '
                </url>
            </urlset>';
                $this->write_out_sitemap($xml, $old_year_month);
                
                $change_month = false;
                $old_url = '';
                $xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
            }

            //if first url start <url>
            if ( $old_url == '' ) {
                $xml .= '
                <url>
                    <loc>'. $this->escape_xml_entities($image['parent_url']) .'</loc>';
                $old_url = $image['parent_url'];
            }

            //if same url insert into <url>
            if ( $image['parent_url'] == $old_url ) {
                $caption = $this->rich_img_data($image['caption']);
                $title = $this->rich_img_data($image['title']);
                if ($caption === $title) $caption = "";

                if ($title === "" ) {
                    $xml .= '
                    <image:image>
                        <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                    </image:image>';
                } else if ($caption === "") {
                    $xml .= '
                    <image:image>
                        <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                        <image:title>'. $this->escape_xml_entities($image['title']) .'</image:title>
                    </image:image>';
                } else {
                    $xml .= '
                    <image:image>
                        <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                        <image:caption>'. $this->escape_xml_entities($image['caption']) .'</image:caption>
                        <image:title>' . $this->escape_xml_entities($image['title']) .'</image:title>
                    </image:image>';
                }
                
                $old_url = $image['parent_url'];
                continue;
            }

            //if new url, end old <url> and start new
            if ( $image['parent_url'] != $old_url ) {
                $caption = $this->rich_img_data($image['caption']);
                $title = $this->rich_img_data($image['title']);
                if ($caption === $title) $caption = "";

                if ($title === "") {
                    $xml .= '
                    </url>
                    <url>
                        <loc>'. $this->escape_xml_entities($image['parent_url']) .'</loc>
                        <image:image>
                            <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                        </image:image>';
                } else if ($caption === "") {
                    $xml .= '
                    </url>
                    <url>
                        <loc>'. $this->escape_xml_entities($image['parent_url']) .'</loc>
                        <image:image>
                            <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                            <image:title>'. $this->escape_xml_entities($image['title']) .'</image:title>
                        </image:image>';
                } else {
                    $xml .= '
                    </url>
                    <url>
                        <loc>'. $this->escape_xml_entities($image['parent_url']) .'</loc>
                        <image:image>
                            <image:loc>'. $this->escape_xml_entities($image['image_file']) .'</image:loc>
                            <image:caption>'. $this->escape_xml_entities($image['caption']) .'</image:caption>
                            <image:title>'. $this->escape_xml_entities($image['title']) .'</image:title>
                        </image:image>';
                }
                $old_url = $image['parent_url'];
                continue;
            }
        }
    }

    public function index_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $files = scandir($_SERVER["DOCUMENT_ROOT"].'/sitemaps-images/');
        foreach ( $files as $file ) {
            if ( strpos($file, '.xml') === false ) continue;
            $xml .= '
            <sitemap>
                <loc>' .get_site_url(). '/sitemaps-images/' .$file. '</loc>
            </sitemap>
            ';
        }
        $xml .= '</sitemapindex>';
        $this->write_out_sitemap($xml, 'index', false);
    }

    private function write_out_sitemap($xml, $sitemap_name, $dir = true) {
        if ( $dir ) $image_sitemap_url = $_SERVER["DOCUMENT_ROOT"].'/sitemaps-images/sitemap-images-'.$sitemap_name.'.xml';
        if ( !$dir ) $image_sitemap_url = $_SERVER["DOCUMENT_ROOT"].'/sitemap-images-'.$sitemap_name.'.xml';
        
        if($this->is_file_writable($_SERVER["DOCUMENT_ROOT"]) || $this->is_file_writable($image_sitemap_url)) {
                if(file_put_contents($image_sitemap_url, $xml)) {
                        return;
                }
        }

        return -1;
    }

    
    private function rich_img_data($data) {
    
        $marcas = [
            'abarth', 'alfa-romeo', 'alpine', 'aston-martin', 'audi', 'bentley', 'bmw', 'bugati', 'citroen', 'cupra', 'dacia', 'dfsk', 'ds', 'ferrari', 'fiat', 'ford', 'hispano-suiza', 'honda', 'hyundai', 'jaguar', 'jeep', 'kia', 'koenigsegg', 'lamorghini', 'land-rover', 'lexus', 'lotus', 'maserati', 'mazda', 'mclaren', 'mercedes-benz', 'mg', 'mini', 'mitsubishi', 'nissan', 'opel', 'pagani', 'peugeot', 'polestar', 'porsche', 'renault', 'rolls-royce', 'seat', 'skoda','smart', 'ssangyong', 'subaru', 'suzuki', 'tesla', 'toyota', 'volkswagen', 'volvo'
        ];
 
        foreach ($marcas as $marca) {
            if (strpos(strtolower($data), $marca) !== false) {
                return $data;
            }
        }
    
        $data = "";
        return $data;
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