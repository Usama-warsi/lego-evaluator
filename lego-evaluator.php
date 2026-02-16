<?php
/**
 * Plugin Name: Toy Exchange LEGO Evaluator
 * Plugin URI: https://toy-exchange.co.uk/
 * Description: Custom tool to evaluate LEGO sets using Bricklink API and add to WooCommerce cart.
 * Author: Antigravity
 * Version: 1.0.0
 * Text Domain: toy-exchange-evaluator
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'TEE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TEE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TEE_VERSION', '1.0.0' );

// Include required files
require_once TEE_PLUGIN_DIR . 'includes/admin-settings.php';
require_once TEE_PLUGIN_DIR . 'includes/bricklink-api.php';
require_once TEE_PLUGIN_DIR . 'includes/evaluator-logic.php';

/**
 * Initialize the plugin
 */
class ToyExchangeEvaluator {
    public function __construct() {
        // Activation & Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        
        // Load scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // Add shortcode for the evaluator tool
        add_shortcode( 'lego_evaluator', array( $this, 'render_evaluator' ) );

        // AJAX handlers
        add_action( 'wp_ajax_tee_evaluate_set', array( $this, 'ajax_evaluate_set' ) );
        add_action( 'wp_ajax_nopriv_tee_evaluate_set', array( $this, 'ajax_evaluate_set' ) );
        
        add_action( 'wp_ajax_tee_calculate_offer', array( $this, 'ajax_calculate_offer' ) );
        add_action( 'wp_ajax_nopriv_tee_calculate_offer', array( $this, 'ajax_calculate_offer' ) );
        
        add_action( 'wp_ajax_tee_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_tee_add_to_cart', array( $this, 'ajax_add_to_cart' ) );

        add_action( 'wp_head', array( $this, 'output_custom_css' ) );
    }

    public function activate() {
        // Initial setup if needed
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'tee-frontend-styles', TEE_PLUGIN_URL . 'assets/css/frontend-styles.css', array(), TEE_VERSION );
        wp_enqueue_script( 'tee-frontend-ui', TEE_PLUGIN_URL . 'assets/js/frontend-ui.js', array( 'jquery' ), TEE_VERSION, true );
        
        wp_localize_script( 'tee-frontend-ui', 'tee_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tee_nonce' ),
            'product_id' => get_option( 'tee_default_product_id' )
        ) );
    }

    /**
     * Output custom CSS based on settings
     */
    public function output_custom_css() {
        $primary        = get_option( 'tee_primary_color', '#1a1a1a' );
        $accent         = get_option( 'tee_accent_color', '#10b981' );
        $result_bg      = get_option( 'tee_result_bg_color', '#ecfdf5' );
        $max_width      = get_option( 'tee_max_width', '800px' );
        $margin         = get_option( 'tee_margin', '20px auto' );
        $font_family    = get_option( 'tee_font_family', 'inherit' );
        $text_color     = get_option( 'tee_text_color', 'inherit' );
        $card_bg        = get_option( 'tee_card_bg', '#ffffff' );
        $border_color   = get_option( 'tee_border_color', '#ebedf0' );
        $bg_subtle      = get_option( 'tee_bg_subtle', '#f9fafb' );
        $success_border = get_option( 'tee_success_border', '#d1fae5' );
        ?>
        <style id="tee-custom-styles">
            :root {
                --tee-primary: <?php echo esc_attr( $primary ); ?>;
                --tee-accent: <?php echo esc_attr( $accent ); ?>;
                --tee-success-bg: <?php echo esc_attr( $result_bg ); ?>;
                --tee-text: <?php echo esc_attr( $text_color ); ?>;
                --tee-bg-card: <?php echo esc_attr( $card_bg ); ?>;
                --tee-border: <?php echo esc_attr( $border_color ); ?>;
                --tee-bg-subtle: <?php echo esc_attr( $bg_subtle ); ?>;
                --tee-success-border: <?php echo esc_attr( $success_border ); ?>;

                /* Core Design Tokens */
                --tee-text-muted: #606266;
                --tee-text-dim: #909399;
                --tee-error: #dc2626;
                --tee-white: #ffffff;
                --tee-shadow-soft: rgba(0, 0, 0, 0.05);
                --tee-radius-lg: 12px;
                --tee-radius-md: 8px;
            }
            .tee-redesign-wrap {
                max-width: <?php echo esc_attr( $max_width ); ?>;
                margin: <?php echo esc_attr( $margin ); ?>;
                font-family: <?php echo esc_attr( $font_family ); ?>;
                color: var(--tee-text);
                -webkit-font-smoothing: antialiased;
            }
            .tee-cond-card.active { border-color: var(--tee-primary) !important; }
            .tee-switch input:checked + .tee-slider { background-color: var(--tee-primary) !important; }
            .tee-offer-price { color: var(--tee-accent) !important; }
            .tee-btn-green { background-color: var(--tee-accent) !important; }
            .tee-result-banner { 
                background-color: var(--tee-success-bg) !important; 
                border-color: var(--tee-success-border) !important;
            }
            .tee-card { background-color: var(--tee-bg-card); border-color: var(--tee-border); }
            .tee-step-box { background-color: var(--tee-bg-subtle); border-color: var(--tee-border); }
        </style>
        <?php
    }

    public function render_evaluator() {
        ob_start();
        include TEE_PLUGIN_DIR . 'templates/evaluator-form.php';
        return ob_get_clean();
    }

    public function ajax_evaluate_set() {
        check_ajax_referer( 'tee_nonce', 'nonce' );
        
        $set_number = sanitize_text_field( $_POST['set_number'] );
        $api = new TEE_Bricklink_API();
        
        // Fetch item details
        $item_details = $api->get_item_data( $set_number );
        if ( is_wp_error( $item_details ) ) {
            wp_send_json_error( $item_details->get_error_message() );
        }

        // Fetch Price Guide (New and Used)
        $new_price_guide = $api->get_price_guide( $set_number, 'N' );
        $used_price_guide = $api->get_price_guide( $set_number, 'U' );

        $new_avg = 0;
        $used_avg = 0;

        if ( ! is_wp_error( $new_price_guide ) ) {
            $new_avg = isset( $new_price_guide['avg_price'] ) ? (float)$new_price_guide['avg_price'] : 0;
        } else {
            error_log( 'TEE Bricklink New Price Error: ' . $new_price_guide->get_error_message() );
        }

        if ( ! is_wp_error( $used_price_guide ) ) {
            $used_avg = isset( $used_price_guide['avg_price'] ) ? (float)$used_price_guide['avg_price'] : 0;
        } else {
            error_log( 'TEE Bricklink Used Price Error: ' . $used_price_guide->get_error_message() );
        }

        // Fetch Subsets (Minifigures)
        $subsets = $api->get_subsets( $set_number );
        $minifigs = array();
        $minifigs_total_value = 0;

        if ( ! is_wp_error( $subsets ) ) {
            foreach ( $subsets as $subset ) {
                if ( isset( $subset['entries'] ) ) {
                    foreach ( $subset['entries'] as $entry ) {
                        if ( $entry['item']['type'] === 'MINIFIG' ) {
                            $m_no = $entry['item']['no'];
                            $m_price_data = $api->get_price_guide( $m_no, 'U', 'MINIFIG' );
                            $m_price = ! is_wp_error( $m_price_data ) && isset( $m_price_data['avg_price'] ) ? (float)$m_price_data['avg_price'] : 0;
                            
                            $minifigs[$m_no] = array(
                                'no'    => $m_no,
                                'name'  => $entry['item']['name'],
                                'qty'   => $entry['quantity'],
                                'price' => $m_price,
                                'thumbnail' => "https://img.bricklink.com/ItemImage/ML/{$m_no}.png"
                            );
                            $minifigs_total_value += ($m_price * $entry['quantity']);
                        }
                    }
                }
            }
        }

        $response_data = array(
            'id'             => $item_details['no'],
            'name'           => $item_details['name'],
            'weight'         => $item_details['weight'] ?? 0,
            'image'          => (isset($item_details['image_url']) && strpos($item_details['image_url'], '//') === 0) ? 'https:' . $item_details['image_url'] : ($item_details['image_url'] ?? "https://img.bricklink.com/ItemImage/SN/{$item_details['no']}-1.png"),
            'prices'         => array(
                'new_avg'    => $new_avg,
                'used_avg'   => $used_avg,
            ),
            'minifigs_data'  => $minifigs,
            'minifigs_value' => $minifigs_total_value
        );
        
        wp_send_json_success( $response_data );
    }

    public function ajax_calculate_offer() {
        check_ajax_referer( 'tee_nonce', 'nonce' );
        
        $set_data = $_POST['set_data'];
        $raw_inputs = $_POST['user_inputs'];
        $user_inputs = array();

        // Ensure booleans are correctly parsed from AJAX strings
        foreach ( $raw_inputs as $key => $value ) {
            if ( is_array( $value ) ) {
                $user_inputs[$key] = $value;
            } else {
                $user_inputs[$key] = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
                if ( is_null( $user_inputs[$key] ) ) {
                    $user_inputs[$key] = $value; // Keep original if not a bool-like string
                }
            }
        }
        
        $evaluator = new TEE_Evaluator_Logic();
        $offer = $evaluator->calculate_offer( $set_data, $user_inputs );
        
        wp_send_json_success( array( 
            'offer' => number_format( $offer, 2 ),
            'weight' => $set_data['weight']
        ) );
    }

    public function ajax_add_to_cart() {
        check_ajax_referer( 'tee_nonce', 'nonce' );
        
        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( 'WooCommerce is not active' );
        }

        $product_id = intval( $_POST['product_id'] );
        $variation_id = 0;
        $price = floatval( $_POST['price'] );
        $metadata = $_POST['metadata'];
        $image = isset( $metadata['image'] ) ? esc_url_raw( $metadata['image'] ) : '';
        
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( 'Product not found' );
        }

        // Handle Variable Products
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_visible_children();
            if ( ! empty( $variations ) ) {
                $variation_id = $variations[0]; // Pick the first variation
            } else {
                wp_send_json_error( 'Variable product has no variations. Please use a Simple Product.' );
            }
        }

        // Custom cart item data to handle the evaluation details
        $cart_item_data = array(
            'tee_evaluation' => array(
                'price' => $price,
                'details' => $metadata,
                'image' => $image
            )
        );

        $passed = WC()->cart->add_to_cart( $product_id, 1, $variation_id, array(), $cart_item_data );
        
        if ( $passed ) {
            wp_send_json_success( array( 'redirect' => wc_get_cart_url() ) );
        } else {
            $notices = wc_get_notices( 'error' );
            $error_msg = 'Could not add to cart (Product ID: ' . $product_id . ')';
            if ( ! empty( $notices ) ) {
                $error_msg .= ': ' . wp_strip_all_tags( $notices[0]['notice'] );
                wc_clear_notices();
            }
            wp_send_json_error( $error_msg );
        }
    }
}

// Instantiate the plugin
new ToyExchangeEvaluator();

/**
 * Filter to override product price in cart based on evaluation
 */
add_action( 'woocommerce_before_calculate_totals', 'tee_override_cart_item_price', 10, 1 );
function tee_override_cart_item_price( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( isset( $cart_item['tee_evaluation']['price'] ) ) {
            $price = $cart_item['tee_evaluation']['price'];
            $cart_item['data']->set_price( $price );
        }
    }
}

/**
 * Display evaluation details in cart/checkout
 */
add_filter( 'woocommerce_get_item_data', 'tee_display_cart_item_evaluation_details', 10, 2 );
function tee_display_cart_item_evaluation_details( $item_data, $cart_item ) {
    if ( isset( $cart_item['tee_evaluation']['details'] ) ) {
        foreach ( $cart_item['tee_evaluation']['details'] as $label => $value ) {
            if ( $label === 'image' ) continue; // Skip image URL in display
            $item_data[] = array(
                'name'  => $label,
                'value' => $value,
            );
        }
    }
    return $item_data;
}

/**
 * Add evaluation details to order items
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'tee_add_evaluation_to_order_items', 10, 4 );
function tee_add_evaluation_to_order_items( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['tee_evaluation']['details'] ) ) {
        foreach ( $values['tee_evaluation']['details'] as $label => $value ) {
            if ( $label === 'image' ) continue; // Don't add image URL as text meta
            $item->add_meta_data( $label, $value );
        }
    }
}

/**
 * Override cart item thumbnail with LEGO Set image
 */
add_filter( 'woocommerce_cart_item_thumbnail', 'tee_override_cart_item_thumbnail', 10, 3 );
function tee_override_cart_item_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {
    if ( isset( $cart_item['tee_evaluation']['image'] ) && ! empty( $cart_item['tee_evaluation']['image'] ) ) {
        return '<img src="' . esc_url( $cart_item['tee_evaluation']['image'] ) . '" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="LEGO Set" style="width:60px; height:auto;">';
    }
    return $thumbnail;
}
