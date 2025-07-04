<?php
/*
Plugin Name: Masonry Photo Gallery
Plugin URI: https://example.com
Description: Galerie d'images en Masonry responsive.
Version: 1.0.0
Author: Example Author
Author URI: https://example.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: masonry-photo-gallery
Domain Path: /languages

Installation:
1. Téléversez le dossier du plugin dans le répertoire wp-content/plugins/.
2. Activez le plugin depuis l'interface d'administration de WordPress.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MPG_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPG_URL', plugin_dir_url( __FILE__ ) );

// Enregistrement du Custom Post Type.
function mpg_register_cpt() {
    $labels = [
        'name'               => __( 'Galleries', 'masonry-photo-gallery' ),
        'singular_name'      => __( 'Gallery', 'masonry-photo-gallery' ),
        'add_new_item'       => __( 'Add New Gallery', 'masonry-photo-gallery' ),
        'edit_item'          => __( 'Edit Gallery', 'masonry-photo-gallery' ),
        'new_item'           => __( 'New Gallery', 'masonry-photo-gallery' ),
        'view_item'          => __( 'View Gallery', 'masonry-photo-gallery' ),
        'search_items'       => __( 'Search Galleries', 'masonry-photo-gallery' ),
        'not_found'          => __( 'No Galleries Found', 'masonry-photo-gallery' ),
        'not_found_in_trash' => __( 'No Galleries found in Trash', 'masonry-photo-gallery' ),
    ];

    $args = [
        'labels'       => $labels,
        'public'       => true,
        'show_in_rest' => true,
        'supports'     => [ 'title' ],
        'menu_icon'    => 'dashicons-format-gallery',
        'rewrite'      => [ 'slug' => 'masonry-gallery' ],
    ];

    register_post_type( 'masonry_gallery', $args );
}
add_action( 'init', 'mpg_register_cpt' );

// Enregistrement de la métadonnée contenant les IDs d'images.
function mpg_register_meta() {
    register_post_meta( 'masonry_gallery', '_masonry_gallery_images', [
        'show_in_rest' => true,
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
    ] );
}
add_action( 'init', 'mpg_register_meta' );

// Ajout de la meta box.
function mpg_add_meta_box() {
    add_meta_box(
        'mpg_images_box',
        __( 'Images de la galerie', 'masonry-photo-gallery' ),
        'mpg_meta_box_callback',
        'masonry_gallery'
    );
}
add_action( 'add_meta_boxes', 'mpg_add_meta_box' );

// Affichage de la meta box.
function mpg_meta_box_callback( $post ) {
    wp_nonce_field( 'mpg_save_images', 'mpg_images_nonce' );
    $value = get_post_meta( $post->ID, '_masonry_gallery_images', true );
    $ids   = array_filter( array_map( 'absint', explode( ',', $value ) ) );
    ?>
    <div id="mpg-container">
        <p>
            <button type="button" class="button" id="mpg-add-images">
                <?php esc_html_e( 'Ajouter des images', 'masonry-photo-gallery' ); ?>
            </button>
        </p>
        <ul id="mpg-images" class="masonry-images">
            <?php
            if ( $ids ) {
                foreach ( $ids as $id ) {
                    $src = wp_get_attachment_image_src( $id, 'thumbnail' );
                    if ( $src ) {
                        printf(
                            '<li class="mpg-image" data-id="%1$d"><img src="%2$s" alt="" /><span class="dashicons dashicons-no-alt mpg-remove" title="%3$s"></span></li>',
                            $id,
                            esc_url( $src[0] ),
                            esc_attr__( 'Supprimer', 'masonry-photo-gallery' )
                        );
                    }
                }
            }
            ?>
        </ul>
        <input type="hidden" id="mpg-images-input" name="mpg_images" value="<?php echo esc_attr( $value ); ?>" />
    </div>
    <?php
}

// Sauvegarde des données de la meta box.
function mpg_save_post( $post_id ) {
    if ( ! isset( $_POST['mpg_images_nonce'] ) || ! wp_verify_nonce( $_POST['mpg_images_nonce'], 'mpg_save_images' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['mpg_images'] ) ) {
        $ids = array_filter( array_map( 'absint', explode( ',', $_POST['mpg_images'] ) ) );
        update_post_meta( $post_id, '_masonry_gallery_images', implode( ',', $ids ) );
    }
}
add_action( 'save_post_masonry_gallery', 'mpg_save_post' );

// Enqueue scripts et styles front-end.
function mpg_enqueue_scripts() {
    wp_enqueue_style( 'masonry-photo-gallery', MPG_URL . 'masonry-photo-gallery.css', [], '1.0.0' );
    wp_enqueue_script( 'masonry' );
    wp_enqueue_script( 'masonry-photo-gallery', MPG_URL . 'masonry-photo-gallery.js', [ 'jquery', 'imagesloaded', 'masonry' ], '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'mpg_enqueue_scripts' );

// Enqueue scripts pour l'admin.
function mpg_admin_scripts( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    $screen = get_current_screen();
    if ( 'masonry_gallery' !== $screen->post_type ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 'mpg-admin', MPG_URL . 'masonry-photo-gallery.js', [ 'jquery', 'jquery-ui-sortable' ], '1.0.0', true );
    wp_localize_script( 'mpg-admin', 'mpg_gallery', [
        'select' => __( 'Select images', 'masonry-photo-gallery' ),
        'use'    => __( 'Use images', 'masonry-photo-gallery' ),
        'remove' => __( 'Remove', 'masonry-photo-gallery' ),
    ] );
    wp_enqueue_style( 'masonry-photo-gallery', MPG_URL . 'masonry-photo-gallery.css', [], '1.0.0' );
}
add_action( 'admin_enqueue_scripts', 'mpg_admin_scripts' );

// Shortcode.
function mpg_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'id'      => 0,
        'columns' => 3,
        'gutter'  => 10,
        'manual'  => true,
    ], $atts, 'masonry_gallery' );

    $post_id = absint( $atts['id'] );
    if ( ! $post_id ) {
        return '';
    }
    $meta = get_post_meta( $post_id, '_masonry_gallery_images', true );
    $ids  = array_filter( array_map( 'absint', explode( ',', $meta ) ) );
    if ( empty( $ids ) ) {
        return '';
    }
    if ( ! $atts['manual'] ) {
        sort( $ids );
    }

    $output  = '<div class="masonry-gallery" style="--mpg-columns:' . intval( $atts['columns'] ) . ';--mpg-gutter:' . intval( $atts['gutter'] ) . 'px" aria-label="' . esc_attr__( 'Galerie', 'masonry-photo-gallery' ) . '">';
    foreach ( $ids as $id ) {
        $img = wp_get_attachment_image( $id, 'large', false, [ 'loading' => 'lazy', 'class' => 'masonry-item' ] );
        if ( $img ) {
            $output .= $img;
        }
    }
    $output .= '</div>';
    return $output;
}
add_shortcode( 'masonry_gallery', 'mpg_gallery_shortcode' );

// Enregistrement du bloc Gutenberg.
function mpg_register_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    wp_register_script( 'mpg-block', MPG_URL . 'masonry-photo-gallery.js', [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n' ], '1.0.0', true );
    wp_register_style( 'mpg-block-style', MPG_URL . 'masonry-photo-gallery.css', [], '1.0.0' );

    register_block_type( 'masonry/gallery', [
        'editor_script'   => 'mpg-block',
        'editor_style'    => 'mpg-block-style',
        'style'           => 'masonry-photo-gallery',
        'render_callback' => 'mpg_block_render',
        'attributes'      => [
            'id'      => [ 'type' => 'number' ],
            'columns' => [ 'type' => 'number', 'default' => 3 ],
            'gutter'  => [ 'type' => 'number', 'default' => 10 ],
            'manual'  => [ 'type' => 'boolean', 'default' => true ],
        ],
    ] );
}
add_action( 'init', 'mpg_register_block' );

// Rendu du bloc côté serveur.
function mpg_block_render( $attributes ) {
    $atts = shortcode_atts( [
        'id'      => 0,
        'columns' => 3,
        'gutter'  => 10,
        'manual'  => true,
    ], $attributes );
    return mpg_gallery_shortcode( $atts );
}
