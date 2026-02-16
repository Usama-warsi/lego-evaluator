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
        $set_value = 0;
        $tier_rules = $this->get_tier_rules( $market_prices['used_avg'] ); // Use used_avg to determine tier

        $condition = $user_inputs['condition'];
        $base_price = 0;

        // Fetch deduction rules
        $cond_rules = get_option( 'tee_condition_rules', array() );
        $defaults = array(
            'new_sealed_damaged' => 10,
            'new_open_parts'     => 10,
            'new_open_box_only'  => 7,
            'new_open_ins_only'  => 10,
            'new_open_none'      => 15,
            'used_box_only'      => 5,
            'used_ins_only'      => 7,
            'used_none'          => 10,
            'used_parts'         => 10,
            'used_assembled_bonus' => 5,
            'minifig_multiplier' => 2.0
        );
        $rules = wp_parse_args( $cond_rules, $defaults );

        if ( 'new_sealed' === $condition ) {
            $base_price = $market_prices['new_avg'];
            $payout_pct = $tier_rules['new_sealed'] / 100;
            $offer = $base_price * $payout_pct;

            // Box condition
            if ( $user_inputs['box_condition'] === 'damaged' ) {
                $offer = $offer * ( 1 - ( $rules['new_sealed_damaged'] / 100 ) );
            }
        } 
        elseif ( 'new_open' === $condition ) {
            $base_price = $market_prices['new_avg'];
            $payout_pct = $tier_rules['new_open'] / 100;
            $offer = $base_price * $payout_pct;

            // Box and Instructions
            $box = $user_inputs['has_box'];
            $ins = $user_inputs['has_instructions'];

            if ( $box && ! $ins ) $offer -= ( $offer * ( $rules['new_open_box_only'] / 100 ) );
            elseif ( ! $box && $ins ) $offer -= ( $offer * ( $rules['new_open_ins_only'] / 100 ) );
            elseif ( ! $box && ! $ins ) $offer -= ( $offer * ( $rules['new_open_none'] / 100 ) );
            
            // Completeness and Minifigures for New Open
            if ( ! $user_inputs['is_complete'] ) {
                $offer -= ( $offer * ( $rules['new_open_parts'] / 100 ) );
            }

            if ( ! empty( $user_inputs['missing_minifigs'] ) ) {
                foreach ( $user_inputs['missing_minifigs'] as $missing_id ) {
                    $minifig_price = $set_data['minifigs_data'][$missing_id]['price'] ?? 0;
                    $deduction = $minifig_price * $rules['minifig_multiplier'];
                    $offer -= $deduction;
                }
            }


        } 
        elseif ( 'used' === $condition ) {
            $base_price = $market_prices['used_avg'];
            $payout_pct = $tier_rules['used'] / 100;
            $offer = $base_price * $payout_pct;

            // Box and Instructions
            $box = $user_inputs['has_box'];
            $ins = $user_inputs['has_instructions'];

            if ( $box && ! $ins ) $offer -= ( $offer * ( $rules['used_box_only'] / 100 ) );
            elseif ( ! $box && $ins ) $offer -= ( $offer * ( $rules['used_ins_only'] / 100 ) );
            elseif ( ! $box && ! $ins ) $offer -= ( $offer * ( $rules['used_none'] / 100 ) );

            // Completeness for Used (Parts only)
            if ( ! $user_inputs['is_complete'] ) {
                $offer -= ( $offer * ( $rules['used_parts'] / 100 ) );
            }

            // Minifigure deductions (always apply if missing)
            if ( ! empty( $user_inputs['missing_minifigs'] ) ) {
                foreach ( $user_inputs['missing_minifigs'] as $missing_id ) {
                    $minifig_price = $set_data['minifigs_data'][$missing_id]['price'] ?? 0;
                    $deduction = $minifig_price * $rules['minifig_multiplier'];
                    // Round deduction to nearest 0.50 as well? User didn't specify for deduction, but it helps consistency.
                    // For now, let's keep it exact and round at the end.
                    $offer -= $deduction;
                }
            }

            // Assembled Bonus
            if ( isset( $user_inputs['is_assembled'] ) && $user_inputs['is_assembled'] ) {
                $offer += ( $offer * ( $rules['used_assembled_bonus'] / 100 ) );
            }
        }

        // Final Rounding to nearest £0.50
        $offer = round( $offer * 2 ) / 2;

        return max( 0, $offer );
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
