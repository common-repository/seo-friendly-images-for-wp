<?php
/*
	Plugin Name: SEO Friendly Images for WP
	Plugin URI: http://www.OptimalPlugins.com/
    Description: This plugin automagically insert/override all the image ALT text to increase SEO image search ranking.
It can also automagically insert/override all the image Title.
	Version: 1.1.0
	Author: OptimalPlugins.com
	Author URI: http://www.OptimalPlugins.com/
	License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SEO_FRIENDLY_IMAGES_NAMESPACE', 'optl_sfi_');

require_once('simple_html_dom.php');
require_once('class-seo-friendly-images-admin-helper.php');
require_once('class-seo-friendly-images-admin.php');

SEO_Friendly_Images_Admin::instance();

class SEO_Friendly_Images
{
    public $namespace = '';
    private static $instance;
    public static $options;

    private function __construct()
    {
        $this->namespace = SEO_FRIENDLY_IMAGES_NAMESPACE;
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self;
            self::$instance->actions();
        }

        return self::$instance;
    }

    private function actions()
    {
        if (is_admin) {
            add_filter('plugin_row_meta', array($this, 'plugin_meta_links'), 10, 2);
        }

        add_filter('the_content', array($this, 'filter_content'), 500);
        add_filter('post_thumbnail_html', array($this, 'filter_content'), 500);

        return;
    }

    public function plugin_meta_links($links, $file)
    {
        $doc_link = "<a target='_blank' href='http://www.optimalplugins.com/doc/seo-friendly-images-for-wp'
							title='View documentation'>Documentation</a>";

        $support_link = "<a target='_blank' href='http://www.optimalplugins.com/support/'
							title='Contact Optimal Plugins'>Support</a>";

        if ($file == plugin_basename(__FILE__)) {
            $links[] = $doc_link;
            $links[] = $support_link;
        }

        return $links;
    }

    public function filter_content($html)
    {
        global $post;

        $image_alt_format = get_option( $this->namespace . 'image_alt_format');
        $enable_image_alt     = get_option( $this->namespace . 'enable_image_alt' );
        $override_image_alt     = get_option( $this->namespace . 'override_image_alt' );

        $image_title_format = get_option( $this->namespace . 'image_title_format' );
        $enable_image_title    = get_option( $this->namespace . 'enable_image_title' );
        $override_image_title    = get_option( $this->namespace . 'override_image_title' );

        $post->post_title;

        $category_text = "";

        if (strrpos($image_alt_format, "%category") !== false
            || strrpos($image_title_format, "%category") !== false) {

            $category_list = get_the_category();

            if ($category_list) {

                $i = 0;

                foreach ($category_list as $cat) {

                    if ($i == 0) {
                        $category_text = $cat->slug . $category_text;
                    } else {
                        $category_text = $cat->slug . ' ' . $category_text;
                    }
                    ++$i;
                }
            }
        }

        $tag_text = "";

        if (strrpos($image_alt_format, "%tags") !== false
            || strrpos($image_title_format, "%tags") !== false) {

            $tag_list = get_the_tags();

            if ($tag_list) {

                $i = 0;

                foreach ($tag_list as $tag) {

                    if ($i == 0) {
                        $tag_text = $tag->name . $tag_text;
                    } else {
                        $tag_text = $tag->name . ' ' . $tag_text;
                    }
                    ++$i;
                }
            }
        }

        if ($enable_image_alt == 'on') {

            if ($image_alt_format == '') {
                $image_alt_format = '%name';
            }

            $image_alt_format = str_replace("%title", $post->post_title, $image_alt_format);
            $image_alt_format = str_replace("%category", $category_text, $image_alt_format);
            $image_alt_format = str_replace("%tags", $tag_text, $image_alt_format);
            $image_alt_format = str_replace("%desc", $post->post_excerpt, $image_alt_format);

            $image_alt_format = str_replace('"', '', $image_alt_format);
            $image_alt_format = str_replace("'", "", $image_alt_format);
            $image_alt_format = (str_replace("-", " ", $image_alt_format));
            $image_alt_format = (str_replace("_", " ", $image_alt_format));
        }

        if ($enable_image_title == 'on') {

            if ($enable_image_title == '') {
                $enable_image_title = 'Image of %name';
            }

            $image_title_format = str_replace("%title", $post->post_title, $image_title_format);
            $image_title_format = str_replace("%category", $category_text, $image_title_format);
            $image_title_format = str_replace("%tags", $tag_text, $image_title_format);
            $image_title_format = str_replace("%desc", $post->post_excerpt, $image_title_format);

            $image_title_format = str_replace('"', '', $image_title_format);
            $image_title_format = str_replace("'", "", $image_title_format);
            $image_title_format = str_replace("-", " ", $image_title_format);
            $image_title_format = str_replace("_", " ", $image_title_format);
        }

        $dom = str_get_html($html);

        foreach($dom->find('img') as $element) {

            $image_name = basename($element->src);
            $image_name = pathinfo($image_name)['filename'];

            if ($element->alt == null || $element->alt == '') {
                if ($enable_image_alt) {
                    $element->alt = str_replace("%name", $image_name, $image_alt_format);
                }
            } else {
                if ($override_image_alt) {
                    $element->alt = str_replace("%name", $image_name, $image_alt_format);
                }
            }

            if ($element->title == null || $element->title == '') {
                if ($enable_image_title) {
                    $element->title = str_replace("%name", $image_name, $image_title_format);
                }
            } else {
                if ($override_image_title) {
                    $element->title = str_replace("%name", $image_name, $image_title_format);
                }
            }
        }

        return $dom;
    }
}

SEO_Friendly_Images::getInstance();

?>