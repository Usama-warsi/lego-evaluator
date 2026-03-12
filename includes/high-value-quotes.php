<?php
/**
 * High-Value Quote Submissions Management
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEE_High_Value_Quotes {
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'add_quotes_submenu' ), 20 );
        add_filter( 'manage_tee_quote_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_tee_quote_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_filter( 'parent_file', array( $this, 'highlight_menu' ) );
        
        // AJAX handler for lead submission
        add_action( 'wp_ajax_tee_submit_lead', array( $this, 'ajax_submit_lead' ) );
        add_action( 'wp_ajax_nopriv_tee_submit_lead', array( $this, 'ajax_submit_lead' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => _x( 'Quotes', 'post type general name', 'toy-exchange-evaluator' ),
            'singular_name'      => _x( 'Quote', 'post type singular name', 'toy-exchange-evaluator' ),
            'menu_name'          => _x( 'Quotes', 'admin menu', 'toy-exchange-evaluator' ),
            'name_admin_bar'     => _x( 'Quote', 'add new on admin bar', 'toy-exchange-evaluator' ),
            'add_new'            => _x( 'Add New', 'quote', 'toy-exchange-evaluator' ),
            'add_new_item'       => __( 'Add New Quote', 'toy-exchange-evaluator' ),
            'new_item'           => __( 'New Quote', 'toy-exchange-evaluator' ),
            'edit_item'          => __( 'Edit Quote', 'toy-exchange-evaluator' ),
            'view_item'          => __( 'View Quote', 'toy-exchange-evaluator' ),
            'all_items'          => __( 'All Quotes', 'toy-exchange-evaluator' ),
            'search_items'       => __( 'Search Quotes', 'toy-exchange-evaluator' ),
            'not_found'          => __( 'No quotes found.', 'toy-exchange-evaluator' ),
            'not_found_in_trash' => __( 'No quotes found in Trash.', 'toy-exchange-evaluator' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // We manually add submenu to keep Settings as default
            'query_var'          => true,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' ),
            'capabilities'       => array(
                'create_posts' => false,
            ),
            'map_meta_cap'       => true,
        );

        register_post_type( 'tee_quote', $args );
    }

    /**
     * Add Quotes as a submenu under LEGO Evaluator (after Settings)
     */
    public function add_quotes_submenu() {
        add_submenu_page(
            'tee-settings',
            __( 'Quotes', 'toy-exchange-evaluator' ),
            __( 'Quotes', 'toy-exchange-evaluator' ),
            'manage_options',
            'edit.php?post_type=tee_quote'
        );
    }

    /**
     * Keep LEGO Evaluator menu highlighted when viewing Quotes
     */
    public function highlight_menu( $parent_file ) {
        global $current_screen;
        if ( $current_screen && $current_screen->post_type === 'tee_quote' ) {
            $parent_file = 'tee-settings';
        }
        return $parent_file;
    }

    public function add_custom_columns( $columns ) {
        $new_columns = array(
            'cb'       => $columns['cb'],
            'title'    => __( 'Quote ID', 'toy-exchange-evaluator' ),
            'set_no'   => __( 'Set Number', 'toy-exchange-evaluator' ),
            'name'     => __( 'Customer Name', 'toy-exchange-evaluator' ),
            'email'    => __( 'Email', 'toy-exchange-evaluator' ),
            'phone'    => __( 'Phone', 'toy-exchange-evaluator' ),
            'date'     => $columns['date'],
        );
        return $new_columns;
    }

    public function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'set_no':
                echo esc_html( get_post_meta( $post_id, 'tee_set_number', true ) );
                break;
            case 'name':
                echo esc_html( get_post_meta( $post_id, 'tee_customer_name', true ) );
                break;
            case 'email':
                echo esc_html( get_post_meta( $post_id, 'tee_customer_email', true ) );
                break;
            case 'phone':
                echo esc_html( get_post_meta( $post_id, 'tee_customer_phone', true ) );
                break;
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'tee_quote_details',
            __( 'Quote Details', 'toy-exchange-evaluator' ),
            array( $this, 'render_meta_box' ),
            'tee_quote',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        $name     = get_post_meta( $post->ID, 'tee_customer_name', true );
        $email    = get_post_meta( $post->ID, 'tee_customer_email', true );
        $phone    = get_post_meta( $post->ID, 'tee_customer_phone', true );
        $message  = get_post_meta( $post->ID, 'tee_customer_message', true );
        $set_no   = get_post_meta( $post->ID, 'tee_set_number', true );
        $set_name = get_post_meta( $post->ID, 'tee_set_name', true );
        $photos   = get_post_meta( $post->ID, 'tee_quote_photos', true );
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e( 'Set Information', 'toy-exchange-evaluator' ); ?></th>
                <td><strong><?php echo esc_html( $set_no ); ?> - <?php echo esc_html( $set_name ); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e( 'Customer Name', 'toy-exchange-evaluator' ); ?></th>
                <td><?php echo esc_html( $name ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Email', 'toy-exchange-evaluator' ); ?></th>
                <td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
            </tr>
            <tr>
                <th><?php _e( 'Phone', 'toy-exchange-evaluator' ); ?></th>
                <td><?php echo esc_html( $phone ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Message', 'toy-exchange-evaluator' ); ?></th>
                <td><?php echo nl2br( esc_html( $message ) ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Photos', 'toy-exchange-evaluator' ); ?></th>
                <td>
                    <?php if ( ! empty( $photos ) && is_array( $photos ) ) : ?>
                        <div class="tee-admin-photos" style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <?php foreach ( $photos as $attachment_id ) : ?>
                                <div class="tee-photo-item">
                                    <a href="<?php echo esc_url( wp_get_attachment_url( $attachment_id ) ); ?>" target="_blank">
                                        <?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php _e( 'No photos uploaded.', 'toy-exchange-evaluator' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Handle AJAX High-Value Lead Submission
     */
    public function ajax_submit_lead() {
        check_ajax_referer( 'tee_nonce', 'nonce' );

        $name     = sanitize_text_field( $_POST['name'] );
        $email    = sanitize_email( $_POST['email'] );
        $phone    = sanitize_text_field( $_POST['phone'] );
        $message  = sanitize_textarea_field( $_POST['message'] );
        $set_no   = sanitize_text_field( $_POST['set_number'] );
        $set_name = sanitize_text_field( $_POST['set_name'] );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( __( 'Please enter a valid email address.', 'toy-exchange-evaluator' ) );
        }

        // Create the Quote Post
        $post_id = wp_insert_post( array(
            'post_title'   => sprintf( 'Quote #%s - %s', $set_no, $name ),
            'post_type'    => 'tee_quote',
            'post_status'  => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( __( 'Error saving quote. Please try again.', 'toy-exchange-evaluator' ) );
        }

        // Save Meta
        update_post_meta( $post_id, 'tee_customer_name', $name );
        update_post_meta( $post_id, 'tee_customer_email', $email );
        update_post_meta( $post_id, 'tee_customer_phone', $phone );
        update_post_meta( $post_id, 'tee_customer_message', $message );
        update_post_meta( $post_id, 'tee_set_number', $set_no );
        update_post_meta( $post_id, 'tee_set_name', $set_name );

        // Handle File Uploads
        if ( ! empty( $_FILES['photos'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_ids = array();
            $files = $_FILES['photos'];
            
            // Reformat $_FILES array for multi-upload
            foreach ( $files['name'] as $key => $value ) {
                if ( $files['name'][$key] ) {
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );

                    $_FILES['temp_photo'] = $file;
                    $attachment_id = media_handle_upload( 'temp_photo', $post_id );

                    if ( ! is_wp_error( $attachment_id ) ) {
                        $attachment_ids[] = $attachment_id;
                    }
                }
            }
            
            if ( ! empty( $attachment_ids ) ) {
                update_post_meta( $post_id, 'tee_quote_photos', $attachment_ids );
            }
        }

        wp_send_json_success( array( 'message' => __( 'Thank you! Your quote request has been received.', 'toy-exchange-evaluator' ) ) );
    }
}

new TEE_High_Value_Quotes();
