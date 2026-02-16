<?php
/**
 * Bricklink API Client for Toy Exchange LEGO Evaluator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEE_Bricklink_API {
    private $consumer_key;
    private $consumer_secret;
    private $token_value;
    private $token_secret;
    private $base_url = 'https://api.bricklink.com/api/store/v1/';

    public function __construct() {
        $this->consumer_key    = get_option( 'tee_bricklink_consumer_key' );
        $this->consumer_secret = get_option( 'tee_bricklink_consumer_secret' );
        $this->token_value     = get_option( 'tee_bricklink_token_value' );
        $this->token_secret    = get_option( 'tee_bricklink_token_secret' );
    }

    /**
     * Get item data (Set)
     */
    public function get_item_data( $set_number ) {
        // Bricklink set numbers often end with -1
        if ( strpos( $set_number, '-' ) === false ) {
            $set_number .= '-1';
        }

        $endpoint = "items/SET/{$set_number}";
        return $this->request( 'GET', $endpoint );
    }

    /**
     * Get Price Guide (6-month average)
     */
    public function get_price_guide( $set_number, $new_or_used = 'N', $item_type = 'SET' ) {
        if ( 'SET' === $item_type && strpos( $set_number, '-' ) === false ) {
            $set_number .= '-1';
        }

        // Try GB first
        $query_params = array(
            'new_or_used' => $new_or_used,
            'guide_type'  => 'sold',
            'country_code' => 'GB',
            'currency_code' => 'GBP'
        );

        $endpoint = "items/{$item_type}/{$set_number}/price";
        $data = $this->request( 'GET', $endpoint, $query_params );

        // Fallback to Global Sold if no GB sold data
        if ( is_wp_error( $data ) || empty( $data['avg_price'] ) || (float)$data['avg_price'] == 0 ) {
            unset( $query_params['country_code'] );
            $data = $this->request( 'GET', $endpoint, $query_params );
        }

        // Second fallback to Stock (Items for Sale) if still no sold data
        if ( is_wp_error( $data ) || empty( $data['avg_price'] ) || (float)$data['avg_price'] == 0 ) {
            $query_params['guide_type'] = 'stock';
            // Try global stock
            $data = $this->request( 'GET', $endpoint, $query_params );
        }

        return $data;
    }

    /**
     * Get subset (Minifigures in a set)
     */
    public function get_subsets( $set_number ) {
        if ( strpos( $set_number, '-' ) === false ) {
            $set_number .= '-1';
        }

        $endpoint = "items/SET/{$set_number}/subsets";
        $params = array( 'breakdown' => 'true' );
        return $this->request( 'GET', $endpoint, $params );
    }

    /**
     * Perform OAuth1.0 Request
     */
    private function request( $method, $endpoint, $params = array() ) {
        if ( ! $this->consumer_key || ! $this->consumer_secret ) {
            return new WP_Error( 'missing_credentials', __( 'Bricklink API credentials missing.', 'toy-exchange-evaluator' ) );
        }

        $url = $this->base_url . $endpoint;
        
        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        $oauth_params = array(
            'oauth_consumer_key'     => $this->consumer_key,
            'oauth_token'            => $this->token_value,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => wp_generate_password( 10, false ),
            'oauth_version'          => '1.0',
        );

        $all_params = array_merge( $oauth_params, $params );
        ksort( $all_params );

        $base_string = strtoupper( $method ) . '&' . rawurlencode( $this->base_url . $endpoint ) . '&' . rawurlencode( http_build_query( $all_params, '', '&', PHP_QUERY_RFC3986 ) );
        $signing_key = rawurlencode( $this->consumer_secret ) . '&' . rawurlencode( $this->token_secret );
        $signature   = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) );

        $oauth_params['oauth_signature'] = $signature;
        
        $header_parts = array();
        foreach ( $oauth_params as $key => $value ) {
            $header_parts[] = sprintf( '%s="%s"', rawurlencode( $key ), rawurlencode( $value ) );
        }
        $header = 'OAuth ' . implode( ', ', $header_parts );

        $response = wp_remote_request( $url, array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => $header,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 15
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        // Debug Logging
        if ( $body && get_option( 'tee_debug_mode' ) ) {
            $log_file = TEE_PLUGIN_DIR . 'tee-api-debug.json';
            $current_logs = array();
            if ( file_exists( $log_file ) ) {
                $content = file_get_contents( $log_file );
                $current_logs = json_decode( $content, true ) ?: array();
            }
            
            // Limit logs to last 20 entries
            if ( count( $current_logs ) > 20 ) {
                array_shift( $current_logs );
            }

            $current_logs[] = array(
                'timestamp' => current_time( 'mysql' ),
                'endpoint'  => $endpoint,
                'method'    => $method,
                'params'    => $params,
                'response'  => $body
            );
            file_put_contents( $log_file, json_encode( $current_logs, JSON_PRETTY_PRINT ) );
        }

        if ( isset( $body['meta']['code'] ) && $body['meta']['code'] != 200 ) {
            return new WP_Error( 'api_error', $body['meta']['message'] ?? 'Unknown API error' );
        }

        return $body['data'] ?? $body;
    }
}
