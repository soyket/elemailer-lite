<?php

namespace Elemailer_Lite\Integrations\Elementor;

if ( ! defined( 'ABSPATH' ) ) die( 'Forbidden' );

/**
 * elEmailer lite template builder library class.
 * Handles dynamically notices for lazy developers.
 *
 * @author elEmailer
 * @since 1.0.0
 */

class Library{

    use \Elemailer_Lite\Traits\Singleton;

    /**
     * Initialize library functionality
     *
     * @return void
     * @since 1.0.0
     */
    public function init() {
        add_action( 'elementor/editor/footer', [ $this, 'insert_templates' ] );
        add_action( 'elementor/editor/footer', [ $this, 'register_widget_scripts' ], 99 );
        add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_widget_styles' ] );
        add_action( 'elementor/preview/enqueue_styles', [ $this, 'register_widget_styles' ] );
        add_action( 'wp_ajax_elemailer-lite-remote-request', [ $this, 'remote_request' ] );
        add_action( 'wp_ajax_elemailer-lite-elementor-batch-process', [ $this, 'elementor_batch_process' ] );
        add_action( 'wp_ajax_elemailer-sync-page-builder', [ $this, 'get_sync_page_builder' ] );
    }

    /**
     * Insert Template
     *
     * @return void
     */
    public function insert_templates() {
        ob_start();
        require_once ELE_MAILER_LITE_PLUGIN_PUBLIC_DIR . '/views/templates.php';
        ob_end_flush();
    }
    /**
     * Register module required js on elementor's action.
     *
     * @since 1.0.5
     */
    public function register_widget_scripts() {
        if ( ! in_array( get_post_type(), ['em-form-template'] ) ) {
            return;
        }

        wp_enqueue_script( 'masonry' );
        wp_enqueue_script( 'imagesloaded' );

        $version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : ELE_MAILER_LITE_VERSION;
        wp_enqueue_script( 'elemailer-elementor-admin-page', ELE_MAILER_LITE_PLUGIN_URL . '/integrations/elementor/assets/js/elemailer-elementor-admin-page.js', array( 'jquery', 'wp-util', 'updates', 'masonry', 'imagesloaded' ), $version, true);

        $license_status = false;
        /* translators: %s are link. */
        $license_msg = sprintf( __( 'This is a premium template. you can purchase it from <a href="%s" target="_blank">here</a>.', 'elemailer-lite' ), 'https://elemailer.com/pricing/' );

        $data = apply_filters(
            'elemailer_render_localize_vars',
            array(
                'plugin_name'          => __( 'Elemailer Templates', 'elemailer-lite' ),
                'ajaxurl'              => esc_url( admin_url( 'admin-ajax.php' ) ),
                '_ajax_nonce'          => wp_create_nonce( 'elemailer-lite-check' ),
                'default_page_builder' => $this->get_default_page_builder(),
                'template'             => esc_html__( 'Template', 'elemailer-lite' ),
                'block'                => esc_html__( 'Block', 'elemailer-lite' ),
                'dismiss_text'         => esc_html__( 'Dismiss', 'elemailer-lite' ),
                'install_plugin_text'  => esc_html__( 'Install Required Plugins', 'elemailer-lite' ),
                'license_status'       => $license_status,
                'isPro'                => false,
                'license_msg'          => $license_msg,
                'getProText'           => __( 'Get Access!', 'elemailer-lite' ),
                'syncCompleteMessage'  => __( 'Template library refreshed!', 'elemailer-lite' ),
                'getProURL'            => esc_url( 'https://elemailer.com/pricing/?utm_source=demo-import-panel&utm_campaign=elemailer-lite&utm_medium=wp-dashboard' ),

            )
        );

        wp_localize_script( 'elemailer-elementor-admin-page', 'elemailerElementorData', $data );
    }

    /**
     * Get default page builder list
     *
     * @since 1.0.5
     *
     * @return array $templates
     */
    public function get_default_page_builder() {
        $api_url       = 'https://lib.elemailer.com/wp-json/wp/v2/elemailerlib/';
        $response      = wp_remote_get( $api_url );
        $response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response_body, true );
        $templates     = array();

        if ( empty( $response_data ) ) {
            return $templates;
        }

        foreach ( $response_data as $page_data ) {
            $templates['id-' . $page_data['id']] = [
                'title'                  => $page_data['title']['rendered'],
                'featured-image-url'     => $page_data['featured_image_src']['featured_media_full'],
                'thumbnail-image-url'    => $page_data['featured_image_src']['featured_media_thumbnail'],
                'elemailer-page-api-url' => $api_url . $page_data['id'],
                'dynamic-page'           => 'no',
                'elemailer-page-tag'     => [
                    '423' => 'home'
                ],
                'site-pages-type'         => 'free',
                'site-pages-page-builder' => 'elementor',
                'site-pages-category'     => [
                    '60' => 'business',
                    '61' => 'free',
                ],
                'required-plugins'        => [
                    [
                        'slug' => 'elementor',
                        'init' => 'elementor/elementor.php',
                        'name' => 'Elementor',
                    ]
                ],
            ];
        }

        return $templates;
    }

    /**
     * Elementor Templates sync
     *
     * @since 1.0.5
     */
    public function get_sync_page_builder() {
        check_ajax_referer( 'elemailer-lite-check', '_ajax_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action', 'elemailer-lite' ) );
        }

        wp_send_json_success( $this->get_default_page_builder() );
    }

    /**
     * Register module required style on elementor's action.
     *
     * @since 1.0.5
     */
    public function register_widget_styles() {
        if ( ! in_array( get_post_type(), ['em-form-template'] ) ) {
            return;
        }
        $version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : ELE_MAILER_LITE_VERSION;
        wp_enqueue_style( 'elemailer-elementor-admin-page', ELE_MAILER_LITE_PLUGIN_URL . 'integrations/elementor/assets/css/elemailer-elementor-admin-style.css', false, $version );
        wp_style_add_data( 'elemailer-elementor-admin-page', 'rtl', 'replace' );
    }

    /**
     * Get API params
     *
     * @since 1.0.5
     *
     * @return array
     */
    public function get_api_params() {
        return apply_filters(
            'elemailer_lite_api_params', array(
                'purchase_key' => '',
                'site_url'     => get_site_url(),
                'per-page'     => 15,
                'template_status' => '',
            )
        );
    }

    /**
     * Elementor Templates Request
     *
     * @since 1.0.5
     */
    public function remote_request() {
        check_ajax_referer( 'elemailer-lite-check', '_ajax_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action', 'elemailer-lite' ) );
        }

        $api_url  = isset( $_POST['url'] ) ? sanitize_text_field( $_POST['url'] ) : '';
        $api_url  = add_query_arg( $this->get_api_params(), $api_url );
        $response = wp_remote_get( $api_url );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( wp_remote_retrieve_body( $response ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        wp_send_json_success( $data );
    }

    /**
     * Elementor Batch Process via AJAX
     *
     * @since 1.0.5
     */
    public function elementor_batch_process() {
        // Verify Nonce.
        check_ajax_referer( 'elemailer-lite-check', '_ajax_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'You are not allowed to perform this action', 'elemailer-lite' ) );
        }

        $api_url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

        if ( empty( $api_url ) ) {
            wp_send_json_error( __( 'Invalid API URL.', 'elemailer-lite' ) );
        }

        $response = wp_remote_get( $api_url );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( wp_remote_retrieve_body( $response ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['elementor_data'] ) ) {
            wp_send_json_error( __( 'Invalid Post Meta', 'elemailer-lite' ) );
        }

        $meta    = json_decode( $data['elementor_data'], true );
        $post_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : '';

        if ( empty( $post_id ) || empty( $meta ) ) {
            wp_send_json_error( __( 'Invalid Post ID or Elementor Meta', 'elemailer-lite' ) );
        }

        require_once ELE_MAILER_LITE_PLUGIN_PUBLIC_DIR . '/Elemailer_Elementor_Pages_Import.php';

        $import      = new \Elemailer_Elementor_Pages_Import();
        $import_data = $import->import( $post_id, $meta );

        wp_send_json_success( $import_data );
    }
}
