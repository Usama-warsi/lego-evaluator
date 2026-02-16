<?php
/**
 * Admin settings for Toy Exchange LEGO Evaluator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEE_Admin_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_log_deletion' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function add_menu_page() {
        add_menu_page(
            __( 'LEGO Evaluator', 'toy-exchange-evaluator' ),
            __( 'LEGO Evaluator', 'toy-exchange-evaluator' ),
            'manage_options',
            'tee-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-calculator'
        );
    }

    public function register_settings() {
        register_setting( 'tee_settings_group', 'tee_bricklink_consumer_key' );
        register_setting( 'tee_settings_group', 'tee_bricklink_consumer_secret' );
        register_setting( 'tee_settings_group', 'tee_bricklink_token_value' );
        register_setting( 'tee_settings_group', 'tee_bricklink_token_secret' );
        
        register_setting( 'tee_settings_group', 'tee_pricing_rules' );
        register_setting( 'tee_settings_group', 'tee_condition_rules' );
        register_setting( 'tee_settings_group', 'tee_default_product_id' );
        register_setting( 'tee_settings_group', 'tee_primary_color' );
        register_setting( 'tee_settings_group', 'tee_accent_color' );
        register_setting( 'tee_settings_group', 'tee_result_bg_color' );
        register_setting( 'tee_settings_group', 'tee_max_width' );
        register_setting( 'tee_settings_group', 'tee_margin' );
        register_setting( 'tee_settings_group', 'tee_font_family' );
        register_setting( 'tee_settings_group', 'tee_text_color' );
        register_setting( 'tee_settings_group', 'tee_card_bg' );
        register_setting( 'tee_settings_group', 'tee_border_color' );
        register_setting( 'tee_settings_group', 'tee_bg_subtle' );
        register_setting( 'tee_settings_group', 'tee_success_border' );
        register_setting( 'tee_settings_group', 'tee_debug_mode' );
    }

    public function handle_log_deletion() {
        if ( isset( $_GET['tee_delete_logs'] ) && check_admin_referer( 'tee_delete_logs_action', 'tee_delete_logs_nonce' ) ) {
            $log_file = TEE_PLUGIN_DIR . 'tee-api-debug.json';
            if ( file_exists( $log_file ) ) {
                unlink( $log_file );
                add_settings_error( 'tee_settings_group', 'logs_deleted', __( 'API Logs have been cleared.', 'toy-exchange-evaluator' ), 'updated' );
            }
        }
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'toplevel_page_tee-settings' !== $hook ) {
            return;
        }
        
        wp_enqueue_style( 'tee-admin-styles', TEE_PLUGIN_URL . 'assets/css/admin-styles.css', array(), TEE_VERSION );
        wp_enqueue_script( 'tee-admin-js', TEE_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), TEE_VERSION, true );
    }

    /**
     * Helper to render a tooltip
     */
    private function render_tooltip( $text ) {
        return '<span class="tee-tooltip"><span class="dashicons dashicons-editor-help"></span><span class="tee-tooltip-text">' . esc_html( $text ) . '</span></span>';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap tee-admin-wrap">
            <h1><?php _e( 'LEGO Evaluator Settings', 'toy-exchange-evaluator' ); ?></h1>
            
            <h2 class="nav-tab-wrapper" style="margin-bottom: 0;">
                <a href="#tee-tab-api" class="nav-tab nav-tab-active"><?php _e( 'API Credentials', 'toy-exchange-evaluator' ); ?></a>
                <a href="#tee-tab-pricing" class="nav-tab"><?php _e( 'Pricing Rules', 'toy-exchange-evaluator' ); ?></a>
                <a href="#tee-tab-conditions" class="nav-tab"><?php _e( 'Condition Rules', 'toy-exchange-evaluator' ); ?></a>
                <a href="#tee-tab-styling" class="nav-tab"><?php _e( 'UI Styling', 'toy-exchange-evaluator' ); ?></a>
                <a href="#tee-tab-general" class="nav-tab"><?php _e( 'General Settings', 'toy-exchange-evaluator' ); ?></a>
                <a href="#tee-tab-logs" class="nav-tab"><?php _e( 'Logs', 'toy-exchange-evaluator' ); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'tee_settings_group' ); ?>
                
                <div id="tee-tab-api" class="tee-tab-content active" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <h2><?php _e( 'Bricklink API Credentials', 'toy-exchange-evaluator' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Consumer Key', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Your Bricklink API Consumer Key.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_bricklink_consumer_key" value="<?php echo esc_attr( get_option( 'tee_bricklink_consumer_key' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Consumer Secret', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Your Bricklink API Consumer Secret.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_bricklink_consumer_secret" value="<?php echo esc_attr( get_option( 'tee_bricklink_consumer_secret' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Token Value', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Your Bricklink API Token Value.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_bricklink_token_value" value="<?php echo esc_attr( get_option( 'tee_bricklink_token_value' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Token Secret', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Your Bricklink API Token Secret.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_bricklink_token_secret" value="<?php echo esc_attr( get_option( 'tee_bricklink_token_secret' ) ); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <div id="tee-tab-pricing" class="tee-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <h2><?php _e( 'Pricing Tier Rules', 'toy-exchange-evaluator' ); ?></h2>
                    <p><?php _e( 'Define different percentage payouts based on the market value of the set.', 'toy-exchange-evaluator' ); ?></p>
                    <div id="tee-pricing-rules-container">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Min Value (£)', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Minimum market value for this tier.', 'toy-exchange-evaluator' ) ); ?></th>
                                    <th><?php _e( 'Max Value (£)', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Maximum market value for this tier.', 'toy-exchange-evaluator' ) ); ?></th>
                                    <th><?php _e( 'New Sealed %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Payout percentage for New & Sealed sets in this value range.', 'toy-exchange-evaluator' ) ); ?></th>
                                    <th><?php _e( 'New Open Complete %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Payout percentage for sets that are New but the box is open.', 'toy-exchange-evaluator' ) ); ?></th>
                                    <th><?php _e( 'Used %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Base payout percentage for Used sets in this value range.', 'toy-exchange-evaluator' ) ); ?></th>
                                    <th><?php _e( 'Actions', 'toy-exchange-evaluator' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="tee-rules-tbody">
                                <?php
                                $rules = get_option( 'tee_pricing_rules', array() );
                                if ( ! is_array( $rules ) ) $rules = array();
                                if ( empty( $rules ) ) {
                                    $rules[] = array( 'min' => 0, 'max' => 50, 'new_sealed' => 70, 'new_open' => 55, 'used' => 50 );
                                }
                                foreach ( $rules as $index => $rule ) :
                                ?>
                                <tr class="rule-row">
                                    <td><input type="number" step="0.01" name="tee_pricing_rules[<?php echo $index; ?>][min]" value="<?php echo esc_attr( $rule['min'] ); ?>"></td>
                                    <td><input type="number" step="0.01" name="tee_pricing_rules[<?php echo $index; ?>][max]" value="<?php echo esc_attr( $rule['max'] ); ?>"></td>
                                    <td><input type="number" step="0.1" name="tee_pricing_rules[<?php echo $index; ?>][new_sealed]" value="<?php echo esc_attr( $rule['new_sealed'] ); ?>">%</td>
                                    <td><input type="number" step="0.1" name="tee_pricing_rules[<?php echo $index; ?>][new_open]" value="<?php echo esc_attr( $rule['new_open'] ); ?>">%</td>
                                    <td><input type="number" step="0.1" name="tee_pricing_rules[<?php echo $index; ?>][used]" value="<?php echo esc_attr( $rule['used'] ); ?>">%</td>
                                    <td><button type="button" class="button remove-rule"><?php _e( 'Remove', 'toy-exchange-evaluator' ); ?></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p><button type="button" id="add-rule" class="button"><?php _e( 'Add New Tier', 'toy-exchange-evaluator' ); ?></button></p>
                    </div>
                </div>

                <div id="tee-tab-conditions" class="tee-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <?php
                    $cond_rules = get_option( 'tee_condition_rules', array() );
                    $defaults = array(
                        'new_sealed_like_new' => 70,
                        'new_sealed_fair'     => 65,
                        'new_sealed_bad'      => 55,
                        'new_open_complete'   => 50,
                        'new_open_incomplete_rate' => 6.50,
                        'used_100_built'      => 55,
                        'used_100_unbuilt'    => 45,
                        'used_95_built'       => 40,
                        'used_95_unbuilt'     => 30,
                        'used_mixed_rate'     => 4.25,
                        'minifig_mixed_value_pct' => 25,
                        'used_box_only'       => 5,
                        'used_ins_only'       => 7,
                        'used_none'           => 10,
                        'used_assembled_bonus' => 5,
                        'minifig_multiplier'  => 2.0,
                        'max_box_weight'      => 18,
                        'rejection_url'       => 'https://toy-exchange.co.uk/sell-mixed-lego/'
                    );
                    $cond_rules = wp_parse_args( $cond_rules, $defaults );
                    ?>
                    <h2><?php _e( 'Condition Deduction Rules', 'toy-exchange-evaluator' ); ?></h2>
                    <p><?php _e( 'Specify the percentage to deduct for each condition penalty, and the multiplier for missing minifigures.', 'toy-exchange-evaluator' ); ?></p>
                    
<h3><?php _e( 'Global Rules', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Minifigure Deduction Multiplier', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Multiplier applied to the average market price of a minifigure to calculate deduction for missing ones.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td>
                                <input type="number" step="0.1" name="tee_condition_rules[minifig_multiplier]" value="<?php echo esc_attr( $cond_rules['minifig_multiplier'] ); ?>" class="small-text">
                                <p class="description"><?php _e( 'Multiplied by market value for missing minifigures.', 'toy-exchange-evaluator' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Max Box Weight (KG)', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The maximum allowed weight for a single shipment. Exceeding this prevents a quote.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td>
                                <input type="number" step="0.1" name="tee_condition_rules[max_box_weight]" value="<?php echo esc_attr( $cond_rules['max_box_weight'] ); ?>" class="small-text">
                                <p class="description"><?php _e( 'Maximum shipping weight for a single quote (e.g. 18.0).', 'toy-exchange-evaluator' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Rejection Redirect URL', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The page users are sent to if their set is rejected (e.g., too many missing parts).', 'toy-exchange-evaluator' ) ); ?></th>
                            <td>
                                <input type="url" name="tee_condition_rules[rejection_url]" value="<?php echo esc_url( $cond_rules['rejection_url'] ); ?>" class="regular-text">
                                <p class="description"><?php _e( 'URL to redirect users to when a quote is rejected.', 'toy-exchange-evaluator' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e( 'New Flow (Seals Intact)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Like New Condition %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets with seals intact and box in perfect condition.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[new_sealed_like_new]" value="<?php echo esc_attr( $cond_rules['new_sealed_like_new'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Fair Condition %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets with seals intact but box has minor wear.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[new_sealed_fair]" value="<?php echo esc_attr( $cond_rules['new_sealed_fair'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Bad Condition %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets with seals intact but box is heavily damaged.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[new_sealed_bad]" value="<?php echo esc_attr( $cond_rules['new_sealed_bad'] ); ?>"> %</td>
                        </tr>
                    </table>

                    <h3><?php _e( 'New Flow (Seals Broken)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Complete Set %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for New (box open) sets that are verified 100% complete.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[new_open_complete]" value="<?php echo esc_attr( $cond_rules['new_open_complete'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Incomplete Rate (£/KG)', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The price paid per KG for New (box open) sets that are missing parts.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.01" name="tee_condition_rules[new_open_incomplete_rate]" value="<?php echo esc_attr( $cond_rules['new_open_incomplete_rate'] ); ?>"> £</td>
                        </tr>
                    </table>

                    <h3><?php _e( 'Used Flow (100% Complete)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Built %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets that are used, complete, and already assembled.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_100_built]" value="<?php echo esc_attr( $cond_rules['used_100_built'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Unbuilt %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets that are used, complete, but disassembled.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_100_unbuilt]" value="<?php echo esc_attr( $cond_rules['used_100_unbuilt'] ); ?>"> %</td>
                        </tr>
                    </table>

                    <h3><?php _e( 'Used Flow (Over 95% Complete)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Built %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets that are used, over 95% complete, and already assembled.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_95_built]" value="<?php echo esc_attr( $cond_rules['used_95_built'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Unbuilt %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Percentage of market value paid for sets that are used, over 95% complete, but disassembled.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_95_unbuilt]" value="<?php echo esc_attr( $cond_rules['used_95_unbuilt'] ); ?>"> %</td>
                        </tr>
                    </table>

                    <h3><?php _e( 'Used Flow (Under 95% / Mixed)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Mixed LEGO Rate (£/KG)', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The price paid per KG for sets that are incomplete or mixed with other parts.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.01" name="tee_condition_rules[used_mixed_rate]" value="<?php echo esc_attr( $cond_rules['used_mixed_rate'] ); ?>"> £</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Minifigure Value Payout %', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The percentage of market value paid for minifigures when included in a mixed/incomplete set.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[minifig_mixed_value_pct]" value="<?php echo esc_attr( $cond_rules['minifig_mixed_value_pct'] ); ?>"> %</td>
                        </tr>
                    </table>

                    <h3><?php _e( 'Additional Deductions (Box/Instructions)', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Has Box but No Instructions', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Additional deduction percentage applied if the set has its original box but is missing instruction manuals.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_box_only]" value="<?php echo esc_attr( $cond_rules['used_box_only'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Has Instructions but No Box', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Additional deduction percentage applied if the set has instruction manuals but is missing its original box.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_ins_only]" value="<?php echo esc_attr( $cond_rules['used_ins_only'] ); ?>"> %</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Neither Box nor Instructions', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Additional deduction percentage applied if both the original box and instruction manuals are missing.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="number" step="0.1" name="tee_condition_rules[used_none]" value="<?php echo esc_attr( $cond_rules['used_none'] ); ?>"> %</td>
                        </tr>
                    </table>

                </div>

                <div id="tee-tab-styling" class="tee-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <h2><?php _e( 'UI Styling', 'toy-exchange-evaluator' ); ?></h2>
                    
                    <h3><?php _e( 'Layout & Typography', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Container Max Width', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The maximum width of the evaluator interface on the frontend.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_max_width" value="<?php echo esc_attr( get_option( 'tee_max_width', '800px' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Container Margin', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The outer spacing of the evaluator. Use "20px auto" to center it with 20px top/bottom margin.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_margin" value="<?php echo esc_attr( get_option( 'tee_margin', '20px auto' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Font Family', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The font family to use for the evaluator. Set to "inherit" to use your theme font.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_font_family" value="<?php echo esc_attr( get_option( 'tee_font_family', 'inherit' ) ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Text Color', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The base text color for the evaluator.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_text_color" value="<?php echo esc_attr( get_option( 'tee_text_color', 'inherit' ) ); ?>" class="regular-text"></td>
                        </tr>
                    </table>

                    <h3><?php _e( 'Colors', 'toy-exchange-evaluator' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Primary Brand Color', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The main color used for buttons, active states, and highlights.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_primary_color" value="<?php echo esc_attr( get_option( 'tee_primary_color', '#1a1a1a' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Accent/Action Color', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'A secondary color for specific action elements or subtle accents.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_accent_color" value="<?php echo esc_attr( get_option( 'tee_accent_color', '#10b981' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Card Background', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The background color for individual evaluation cards.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_card_bg" value="<?php echo esc_attr( get_option( 'tee_card_bg', '#ffffff' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Border Color', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The color for borders around cards and other UI elements.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_border_color" value="<?php echo esc_attr( get_option( 'tee_border_color', '#ebedf0' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Subtle Background', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'A light background color used for secondary sections or hover states.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_bg_subtle" value="<?php echo esc_attr( get_option( 'tee_bg_subtle', '#f9fafb' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Result Banner Background', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The background color for the final price evaluation banner.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_result_bg_color" value="<?php echo esc_attr( get_option( 'tee_result_bg_color', '#ecfdf5' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Result Banner Border', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'The border color for the final price evaluation banner.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td><input type="text" name="tee_success_border" value="<?php echo esc_attr( get_option( 'tee_success_border', '#d1fae5' ) ); ?>" class="tee-color-picker"></td>
                        </tr>
                    </table>
                </div>

                <div id="tee-tab-general" class="tee-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <h2><?php _e( 'General Settings', 'toy-exchange-evaluator' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Evaluated Product', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Select the WooCommerce product whose price will be overridden by the evaluation quote during checkout.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td>
                                <select name="tee_default_product_id">
                                    <?php
                                    $products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1 ) );
                                    if ( empty( $products ) ) {
                                        echo '<option value="">' . __( 'No products found', 'toy-exchange-evaluator' ) . '</option>';
                                    } else {
                                        foreach ( $products as $product ) {
                                            echo '<option value="' . $product->ID . '" ' . selected( get_option( 'tee_default_product_id' ), $product->ID, false ) . '>' . esc_html( $product->post_title ) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e( 'Select the WooCommerce product used for adding evaluations to cart.', 'toy-exchange-evaluator' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Debug Mode', 'toy-exchange-evaluator' ); ?> <?php echo $this->render_tooltip( __( 'Enable to record Bricklink API requests and responses in the Logs tab for troubleshooting.', 'toy-exchange-evaluator' ) ); ?></th>
                            <td>
                                <input type="checkbox" name="tee_debug_mode" value="1" <?php checked( 1, get_option( 'tee_debug_mode' ), true ); ?>>
                                <p class="description"><?php _e( 'Enable to save API responses and other debug information to logs.', 'toy-exchange-evaluator' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tee-tab-logs" class="tee-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;"><?php _e( 'API Logs', 'toy-exchange-evaluator' ); ?></h2>
                        <?php if ( file_exists( TEE_PLUGIN_DIR . 'tee-api-debug.json' ) ) : ?>
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=tee-settings&tee_delete_logs=1#tee-tab-logs' ), 'tee_delete_logs_action', 'tee_delete_logs_nonce' ); ?>" class="button button-secondary" onclick="return confirm('<?php _e( 'Are you sure you want to delete all logs?', 'toy-exchange-evaluator' ); ?>');">
                                <?php _e( 'Delete Logs', 'toy-exchange-evaluator' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <p><?php _e( 'Recent API requests and responses (Debug Mode must be enabled to collect logs).', 'toy-exchange-evaluator' ); ?></p>
                    <div id="tee-logs-container" style="max-height: 600px; overflow-y: auto; background: #f0f0f1; padding: 15px; border-radius: 4px; border: 1px solid #dcdcde;">
                        <?php
                        $log_file = TEE_PLUGIN_DIR . 'tee-api-debug.json';
                        if ( file_exists( $log_file ) ) {
                            $logs = json_decode( file_get_contents( $log_file ), true );
                            if ( ! empty( $logs ) ) {
                                $logs = array_reverse( $logs ); // Show latest first
                                foreach ( $logs as $log ) {
                                    echo '<div class="tee-log-entry" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #dcdcde;">';
                                    echo '<strong>[' . esc_html( $log['timestamp'] ) . '] ' . esc_html( $log['method'] ) . ' ' . esc_html( $log['endpoint'] ) . '</strong>';
                                    if ( ! empty( $log['params'] ) ) {
                                        echo '<details style="margin-top: 5px;"><summary>' . __( 'Parameters', 'toy-exchange-evaluator' ) . '</summary><pre style="font-size: 11px; background: #fff; padding: 10px; border: 1px solid #dcdcde;">' . esc_html( json_encode( $log['params'], JSON_PRETTY_PRINT ) ) . '</pre></details>';
                                    }
                                    echo '<details style="margin-top: 5px;"><summary>' . __( 'Response', 'toy-exchange-evaluator' ) . '</summary><pre style="font-size: 11px; background: #fff; padding: 10px; border: 1px solid #dcdcde;">' . esc_html( json_encode( $log['response'], JSON_PRETTY_PRINT ) ) . '</pre></details>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>' . __( 'No logs found.', 'toy-exchange-evaluator' ) . '</p>';
                            }
                        } else {
                            echo '<p>' . __( 'Log file not found.', 'toy-exchange-evaluator' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <?php submit_button(); ?>
                </div>
            </form>
        </div>
        <?php
    }
}

new TEE_Admin_Settings();
