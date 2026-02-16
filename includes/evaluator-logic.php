<?php
/**
 * Evaluation Logic for Toy Exchange LEGO Evaluator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEE_Evaluator_Logic {
    public function calculate_offer( $set_data, $user_inputs ) {
        $market_prices = $set_data['prices']; // Contains new_avg and used_avg
        $condition = $user_inputs['condition'];
        $base_price = 0;
        
        // Dynamic Tier Lookup based on the price of the selected condition
        $price_for_tier = ('used' === $condition) ? $market_prices['used_avg'] : $market_prices['new_avg'];
        $tier_rules = $this->get_tier_rules( $price_for_tier ); 

        // Fetch deduction rules
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
            'rejection_url'       => '/shop'
        );
        $rules = wp_parse_args( $cond_rules, $defaults );

        $offer = 0;
        $rejected = false;

        if ( 'new' === $condition ) {
            if ( ! empty( $user_inputs['seals_intact'] ) ) {
                // Seals Yes Branch
                $base_price = $market_prices['new_avg'];
                $box_cond = $user_inputs['box_condition'] ?? 'like_new';
                $pct_key = 'new_sealed_' . $box_cond;
                $pct = $rules[$pct_key] ?? 70;
                $offer = $base_price * ( $pct / 100 );
            } else {
                // Seals No Branch
                if ( ! empty( $user_inputs['is_complete'] ) ) {
                    // Complete
                    $base_price = $market_prices['new_avg'];
                    $pct = $rules['new_open_complete'] ?? 50;
                    $offer = $base_price * ( $pct / 100 );

                    // Minifigure deductions (2x market value) if symbols are unchecked
                    if ( ! empty( $user_inputs['missing_minifigs'] ) ) {
                        foreach ( $user_inputs['missing_minifigs'] as $missing_id => $count ) {
                            $minifig_price = $set_data['minifigs_data'][$missing_id]['price'] ?? 0;
                            $deduction = $minifig_price * $rules['minifig_multiplier'] * $count;
                            $offer -= $deduction;
                        }
                    }
                } else {
                    // Incomplete (Weight based + Minifig value)
                    $weight_grams = (float) ( $user_inputs['weight'] ?? 0 );
                    $weight_kg = $weight_grams / 1000;
                    $weight_payout = $weight_kg * ( $rules['new_open_incomplete_rate'] ?? 6.50 );

                    // Calculate value of SELECTED minifigs (25% rate)
                    $selected_minifigs_value = 0;
                    foreach ( $set_data['minifigs_data'] as $m_id => $m_data ) {
                        $missing_count = $user_inputs['missing_minifigs'][$m_id] ?? 0;
                        $present_count = max( 0, $m_data['qty'] - $missing_count );
                        $selected_minifigs_value += ( $m_data['price'] * $present_count );
                    }
                    
                    $minifig_payout = $selected_minifigs_value * ( ( $rules['minifig_mixed_value_pct'] ?? 25 ) / 100 );
                    $offer = $weight_payout + $minifig_payout;
                }
            }
        } 
        elseif ( 'used' === $condition ) {
            $comp_level = $user_inputs['completion_level'] ?? '100';
            
            if ( '100' === $comp_level || '95' === $comp_level ) {
                $base_price = $market_prices['used_avg'];
                $built = ! empty( $user_inputs['is_built'] );
                $pct_key = ( '100' === $comp_level ) ? ( $built ? 'used_100_built' : 'used_100_unbuilt' ) : ( $built ? 'used_95_built' : 'used_95_unbuilt' );
                $pct = $rules[$pct_key] ?? 50;
                $offer = $base_price * ( $pct / 100 );

                // Deduct for Box/Instructions missing (from initial valuation)
                $initial_valuation = $offer;
                $box = ! empty( $user_inputs['has_box'] );
                $ins = ! empty( $user_inputs['has_instructions'] );

                if ( $box && ! $ins ) $offer -= ( $initial_valuation * ( $rules['used_box_only'] / 100 ) );
                elseif ( ! $box && $ins ) $offer -= ( $initial_valuation * ( $rules['used_ins_only'] / 100 ) );
                elseif ( ! $box && ! $ins ) $offer -= ( $initial_valuation * ( $rules['used_none'] / 100 ) );

                // Minifigure deductions (2x market value)
                if ( ! empty( $user_inputs['missing_minifigs'] ) ) {
                    foreach ( $user_inputs['missing_minifigs'] as $missing_id => $count ) {
                        $minifig_price = $set_data['minifigs_data'][$missing_id]['price'] ?? 0;
                        $deduction = $minifig_price * $rules['minifig_multiplier'] * $count;
                        $offer -= $deduction;
                    }
                }
            } else {
                // Less than 95% Complete (Weight based + Minifig value)
                $weight_grams = (float) ( $user_inputs['weight'] ?? 0 );
                $weight_kg = $weight_grams / 1000;
                $weight_payout = $weight_kg * ( $rules['used_mixed_rate'] ?? 4.25 );
                
                // Calculate value of SELECTED minifigs
                $selected_minifigs_value = 0;
                foreach ( $set_data['minifigs_data'] as $m_id => $m_data ) {
                    $missing_count = $user_inputs['missing_minifigs'][$m_id] ?? 0;
                    $present_count = max( 0, $m_data['qty'] - $missing_count );
                    $selected_minifigs_value += ( $m_data['price'] * $present_count );
                }
                
                $minifig_payout = $selected_minifigs_value * ( ( $rules['minifig_mixed_value_pct'] ?? 25 ) / 100 );
                $offer = $weight_payout + $minifig_payout;
            }
        }

        // Final Rounding to nearest £0.50
        $offer = round( $offer * 2 ) / 2;

        if ( $offer <= 0 ) {
            return array(
                'offer' => 0,
                'rejected' => true,
                'rejection_url' => $rules['rejection_url'] ?? '#'
            );
        }

        return $offer;

    }

    private function get_tier_rules( $market_value ) {
        $rules = get_option( 'tee_pricing_rules', array() );
        foreach ( $rules as $rule ) {
            if ( $market_value >= $rule['min'] && ( $rule['max'] == 0 || $market_value <= $rule['max'] ) ) {
                return $rule;
            }
        }
        // Default fallback if no rules match
        return array( 'new_sealed' => 70, 'new_open' => 55, 'used' => 50 );
    }
}
