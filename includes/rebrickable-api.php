<?php
/**
 * Rebrickable API Client — used for set name/keyword search
 * BrickLink has no search endpoint; Rebrickable provides /api/v3/lego/sets/?search=
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEE_Rebrickable_API {
    private $api_key;
    private $base_url = 'https://rebrickable.com/api/v3/lego/';

    public function __construct() {
        $this->api_key = get_option( 'tee_rebrickable_api_key' );
    }

    /**
     * Search sets by keyword (name or partial set number).
     * Returns array of { set_num, name, year, num_parts, set_img_url } or WP_Error.
     */
    public function search_sets( $query, $page_size = 10 ) {
        if ( ! $this->api_key ) {
            return new WP_Error( 'missing_key', __( 'Rebrickable API key is not configured.', 'toy-exchange-evaluator' ) );
        }

        $url = add_query_arg( array(
            'search'    => $query,
            'page_size' => intval( $page_size ),
            'ordering'  => '-year',
        ), $this->base_url . 'sets/' );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'key ' . $this->api_key,
            ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['results'] ) ) {
            return array();
        }

        $sets = array();
        foreach ( $body['results'] as $set ) {
            // Strip the trailing "-1" variant suffix for display / BrickLink lookup
            $set_num_clean = preg_replace( '/-\d+$/', '', $set['set_num'] );
            $sets[] = array(
                'set_num'  => $set_num_clean,
                'name'     => $set['name'],
                'year'     => $set['year'],
                'parts'    => $set['num_parts'],
                'image'    => $set['set_img_url'] ?? '',
            );
        }

        return $sets;
    }
}
