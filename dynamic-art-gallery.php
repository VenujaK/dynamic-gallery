<?php
/**
 * Plugin Name: Dynamic Art Gallery
 * Description: Creates a dynamic art gallery with filtering by type, artist, and color theme.
 * Version: 1.0
 * Author: VenujaK
 * Author URI:
 * License: GPLv2 or later
 * Text Domain: dynamic-art-gallery
 */

/**
 * Taxanomies and post types
 */
function dag_setup() {
    $labels = array(
        'name' => _x('Artworks', 'Post Type General Name', 'dynamic-art-gallery'),
        'singular_name' => _x('Artwork', 'Post Type Singular Name', 'dynamic-art-gallery'),
    );
    $args = array(
        'label' => _x('Artwork', 'Post Type Singular Name', 'dynamic-art-gallery'),
        'labels' => $labels,
        'description' => 'Post type for storing artwork information',
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-art',
        'supports' => array('title', 'editor', 'thumbnail'),
    );
    register_post_type('artwork', $args);

    //taxonomy
    $labels = array(
        'name' => _x('Art Types', 'Taxonomy General Name', 'dynamic-art-gallery'),
        'singular_name' => _x('Art Type', 'Taxonomy Singular Name', 'dynamic-art-gallery'),
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'singular_slug' => 'art-type',
    );
    register_taxonomy('art_type', array('artwork'), $args);

    // artist
    $labels = array(
        'name' => _x('Artists', 'Taxonomy General Name', 'dynamic-art-gallery'),
        'singular_name' => _x('Artist', 'Taxonomy Singular Name', 'dynamic-art-gallery'),
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'singular_slug' => 'artist',
    );
    register_taxonomy('artist', array('artwork'), $args);

    //color theme
    $labels = array(
        'name' => _x('Color Themes', 'Taxonomy General Name', 'dynamic-art-gallery'),
        'singular_name' => _x('Color Theme', 'Taxonomy Singular Name', 'dynamic-art-gallery'),
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'singular_slug' => 'color-theme',
    );
    register_taxonomy('color_theme', array('artwork'), $args);

    wp_enqueue_script('dag-gallery-script', plugin_dir_url(__FILE__) . 'gallery-script.js', array('jquery'), '1.0', true);
    wp_localize_script('dag-gallery-script', 'dag_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('init', 'dag_setup');


function dynamic_art_gallery_shortcode($atts) {
    // filter section
    $filter_html = '<div class="art-filter">';
    $filter_html .= '<form id="art-filter-form">';
    $filter_html .= '<select name="art_type"><option value="">Select Art Type</option>';
    $art_types = get_terms('art_type', array('hide_empty' => false));
    foreach ($art_types as $art_type) {
        $filter_html .= '<option value="' . $art_type->slug . '">' . $art_type->name . '</option>';
    }
    $filter_html .= '</select>';
    $filter_html .= '<select name="artist"><option value="">Select Artist</option>';
    $artists = get_terms('artist', array('hide_empty' => false));
    foreach ($artists as $artist) {
        $filter_html .= '<option value="' . $artist->slug . '">' . $artist->name . '</option>';
    }
    $filter_html .= '</select>';
    $filter_html .= '<select name="color_theme"><option value="">Select Color Theme</option>';
    $color_themes = get_terms('color_theme', array('hide_empty' => false));
    foreach ($color_themes as $color_theme) {
        $filter_html .= '<option value="' . $color_theme->slug . '">' . $color_theme->name . '</option>';
    }
    $filter_html .= '<input type="submit" class="custom-btn btn-style" value="Filter">';
    $filter_html .= '</form>';
    $filter_html .= '</div>';

    $output = $filter_html;
    $output .= '<div id="art-gallery" class="gallery">';

    $args = array(
        'post_type' => 'artwork',
        'posts_per_page' => -1, 
    );
    $query = new WP_Query($args);

    // display artwork
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<img src="' . get_the_post_thumbnail_url() . '" alt="' . get_the_title() . '">'; 
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No artwork found.</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('dynamic_art_gallery', 'dynamic_art_gallery_shortcode');

// artwork filtering
function dag_filter_gallery() {
    $art_type = isset($_POST['art_type']) ? $_POST['art_type'] : '';
    $artist = isset($_POST['artist']) ? $_POST['artist'] : '';
    $color_theme = isset($_POST['color_theme']) ? $_POST['color_theme'] : '';

    $args = array(
        'post_type' => 'artwork',
        'posts_per_page' => -1, 
        'tax_query' => array(
            'relation' => 'AND',
        ),
    );

    if (!empty($art_type)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'art_type',
            'field' => 'slug',
            'terms' => $art_type,
        );
    }
    if (!empty($artist)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'artist',
            'field' => 'slug',
            'terms' => $artist,
        );
    }
    if (!empty($color_theme)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'color_theme',
            'field' => 'slug',
            'terms' => $color_theme,
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '<div class="gallery">';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<img src="' . get_the_post_thumbnail_url() . '" alt="' . get_the_title() . '">'; 
        }
        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output = '<p>No artwork found.</p>';
    }
    echo $output;
    wp_die();
}
function dag_enqueue_styles() {
    wp_enqueue_style('dag-gallery-style', plugin_dir_url(__FILE__) . 'gallery-style.css');
}
add_action('wp_enqueue_scripts', 'dag_enqueue_styles');

add_action('wp_ajax_dag_filter_gallery', 'dag_filter_gallery');
add_action('wp_ajax_nopriv_dag_filter_gallery', 'dag_filter_gallery'); 
?>