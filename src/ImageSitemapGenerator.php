<?php

class ImageSitemapGenerator 
{

    public function image_sitemap_create() {
        global $wpdb;

        $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type<>'revision' AND post_status IN ('publish','inherit') ORDER BY `wp_posts`.`post_date` DESC");

        $thumbs = $wpdb->get_results("
            SELECT * FROM $wpdb->posts p
            INNER JOIN $wpdb->postmeta pm ON p.id=pm.post_id
            INNER JOIN $wpdb->posts i ON pm.meta_value=i.id
            WHERE p.post_type<>'revision' AND p.post_status='publish' AND pm.meta_key='_thumbnail_id
            ORDER BY `p`.`post_date` DESC'
        ");
    
        if(empty($posts) && empty($thumbs)) {
            return 0;
        } else {
            $images = array();
            foreach($posts as $post) {
                if($post->post_type == 'attachment') {
                    if($post->post_parent != 0 && (get_post_status( $post->post_parent ) == 'publish')) {
                        $images[$post->post_parent][$post->guid] = 1;
                        $images_caption[$post->post_parent][$post->post_content] = 1;
                        $images_title[$post->post_parent][$post->post_title] = 1;
                    }
                } elseif(preg_match_all('/img src=("|\')([^"\']+)("|\')/ui',$post->post_content,$matches,PREG_SET_ORDER)) {
    
                    foreach($matches as $key => $match) {
                        $imgurl = $match[2];
                        if(strtolower(substr($imgurl,0,4)) != 'http') {
                            $imgurl = get_site_url() .$imgurl;
                        }
                        $images[$post->ID][$imgurl] = 1;
                    }
                }
            }
            foreach($thumbs as $post) {
                $images[$post->ID][$post->guid] = 1;
                $images_caption[$post->post_parent][$post->post_content] = 1;
                $images_title[$post->post_parent][$post->post_title] = 1;
            }
            if( count($images) == 0 ) {
                return 0;
            } else {
                $xml  = '<?xml version="1.0" encoding="UTF-8"?>' ."\n";
                $xml .= '<!-- Created by Diariomotor on ' .date("F j, Y, g:i a") .'" -->' ."\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' ."\n";
    
                $limit = 1;
                foreach($images as $k=>$v) {
                    
                    unset($imgurls);
                    foreach(array_keys($v) as $imgurl) {
                        if(is_ssl()) {
                            $imgurl = str_replace('http://','https://',$imgurl);
                        } else {
                            $imgurl = str_replace('https://','http://',$imgurl);
                        }
                        $imgurls[$imgurl] = 1;
                    }
                    $permalink = get_permalink($k);
                    if(!empty($permalink)) {

                        $img = '';
                        foreach( array_keys($imgurls) as $key => $imgurl ) {
                            $caption = $this->ensure_rich_img_data(array_keys($images_caption[$k])[$key]);
                            $title = $this->ensure_rich_img_data(array_keys($images_title[$k])[$key]);
                            if (trim($caption) == trim($title)) {
                                $caption = "";
                            }
    
                            $img .=
                                "<image:image>" .
                                "<image:loc>" .$this->escape_xml_entities($imgurl) ."</image:loc>";
                                
                            if ($caption != "") {
                                $img .= "<image:caption>" .$this->escape_xml_entities($caption) ."</image:caption>";
                            }
                            if ($title != "") {
                                $img .= "<image:title>" .$this->escape_xml_entities($title) ."</image:title>";
                            }
                            $img .= "</image:image>";
                        }
                        $xml .= "<url><loc>" .$this->escape_xml_entities($permalink) ."</loc>" .$img ."</url>";
                        $limit--;
                    }
    
                    if($limit === 0){
                        break;
                    }
                }
    
                $xml .= "</urlset>";
            }
        }
    
        $image_sitemap_url = $_SERVER["DOCUMENT_ROOT"].'/sitemap-images.xml';
        if($this->is_file_writable($_SERVER["DOCUMENT_ROOT"]) || $this->is_file_writable($image_sitemap_url)) {
            if(file_put_contents($image_sitemap_url, $xml)) {
                return count($images);
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