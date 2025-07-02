<?php
/**
 * Plugin Name:       Content Types Block
 * Description:       Registers a `content_type` taxonomy on posts and provides a dynamic Gutenberg block that lists those terms (drop-in replacement for Categories).
 * Version:           1.1.0
 * Author:            Jason Clarke
 * License:           GPL-2.0-or-later
 * Text Domain:       content-types-block
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1) Register the custom taxonomy 'content_type' for Posts
 */
add_action( 'init', 'ctb_register_content_type_taxonomy' );
function ctb_register_content_type_taxonomy() {
    $labels = [
        'name'              => _x( 'Content Types', 'taxonomy general name' ),
        'singular_name'     => _x( 'Content Type', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Content Types' ),
        'all_items'         => __( 'All Content Types' ),
        'parent_item'       => __( 'Parent Content Type' ),
        'parent_item_colon' => __( 'Parent Content Type:' ),
        'edit_item'         => __( 'Edit Content Type' ),
        'update_item'       => __( 'Update Content Type' ),
        'add_new_item'      => __( 'Add New Content Type' ),
        'new_item_name'     => __( 'New Content Type Name' ),
        'menu_name'         => __( 'Content Types' ),
    ];

    $args = [
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true,                  // category-like
        'show_ui'           => true,
        'show_in_rest'      => true,                  // expose in Gutenberg
        'rest_base'         => 'content-types',
        'rewrite'           => [ 'slug' => 'content-type' ],
    ];

    register_taxonomy( 'content_type', [ 'post' ], $args );
}

/**
 * 2) Register the block (reads blocks/content-types/block.json) with a PHP render callback
 */
add_action( 'init', function() {
    register_block_type(
        __DIR__ . '/blocks/content-types',
        [
            'render_callback' => 'ctb_render_content_types_block',
        ]
    );
} );

/**
 * 3) Render callback: outputs a wrapper + <ul> of content_type terms
 *
 * @param array    $attributes Block attributes (unused).
 * @param string   $content    Inner content (unused).
 * @param WP_Block $block      Block instance (for wrapper attrs).
 * @return string              HTML output.
 */
function ctb_render_content_types_block( $attributes, $content, $block ) {
    $terms = get_the_terms( get_the_ID(), 'content_type' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    // If there are no terms, show your own placeholder:
    if ( empty( $terms ) ) {
        return sprintf(
            '<div %1$s><p class="wp-block-contenttypes__empty">%2$s</p></div>',
            // this gives the wrapper classes & inline styles
            get_block_wrapper_attributes( [ 'class' => 'wp-block-contenttypes' ] ),
            esc_html__( 'No Content Types', 'content-types-block' )
        );
    }

    $items = '';
    foreach ( $terms as $term ) {
        $url = get_term_link( $term );
        if ( is_wp_error( $url ) ) {
            continue;
        }
        $items .= sprintf(
            '<li class="wp-block-contenttypes__item"><a href="%s">%s</a></li>',
            esc_url( $url ),
            esc_html( $term->name )
        );
    }

    $list = sprintf(
        '<ul class="wp-block-contenttypes__list">%s</ul>',
        $items
    );

    return sprintf(
        '<div %s>%s</div>',
        get_block_wrapper_attributes( [ 'class' => 'wp-block-contenttypes' ] ),
        $list
    );
}