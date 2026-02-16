<?php
/**
 * LEGO Evaluator Form Template - Redesigned
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="tee-evaluator-container" class="tee-redesign-wrap">
    <!-- Search Section -->
    <div class="tee-card tee-search-card">
        <h3><?php _e( 'Find Your Set', 'toy-exchange-evaluator' ); ?></h3>
        <div class="tee-input-group">
            <input type="text" id="tee-set-number" placeholder="<?php _e( 'sell lego', 'toy-exchange-evaluator' ); ?>" class="tee-input">
            <button id="tee-search-set" class="tee-btn-dark">
                <span class="dashicons dashicons-search"></span> <?php _e( 'Search', 'toy-exchange-evaluator' ); ?>
            </button>
        </div>
        <div id="tee-set-preview" class="tee-set-preview" style="display:none; margin-top:20px;">
            <img id="tee-set-image-thumb" src="" alt="Set Image" style="max-width:150px; border-radius:8px; border:1px solid #ebedf0;">
            <p id="tee-set-name-preview" style="font-weight:700; margin:10px 0 0 0;"></p>
        </div>
        <div id="tee-search-error" class="tee-error" style="display:none;"></div>
        <div id="tee-loading" class="tee-loading" style="display:none;"><?php _e( 'Searching...', 'toy-exchange-evaluator' ); ?></div>
    </div>

    <!-- Condition & Details Section -->
    <div id="tee-main-ui" class="tee-card tee-main-card" style="display:none;">
        <div class="tee-section-header">
            <h3><?php _e( 'Set Condition', 'toy-exchange-evaluator' ); ?></h3>
        </div>

        <div class="tee-form-group">
            <label class="tee-group-label"><?php _e( 'Condition', 'toy-exchange-evaluator' ); ?></label>
            <div class="tee-condition-grid">
                <div class="tee-cond-card" data-cond="new_sealed">
                    <div class="tee-radio-circle"></div>
                    <div class="tee-cond-content">
                        <strong><span class="tee-icon">📦</span> <?php _e( 'New Sealed', 'toy-exchange-evaluator' ); ?></strong>
                        <p><?php _e( 'Factory sealed, never opened', 'toy-exchange-evaluator' ); ?></p>
                    </div>
                </div>
                <div class="tee-cond-card" data-cond="new_open">
                    <div class="tee-radio-circle"></div>
                    <div class="tee-cond-content">
                        <strong><span class="tee-icon">📦</span> <?php _e( 'New Opened', 'toy-exchange-evaluator' ); ?></strong>
                        <p><?php _e( 'Opened but complete, like new', 'toy-exchange-evaluator' ); ?></p>
                    </div>
                </div>
                <div class="tee-cond-card active" data-cond="used">
                    <div class="tee-radio-circle"></div>
                    <div class="tee-cond-content">
                        <strong><span class="tee-icon">🧩</span> <?php _e( 'Used', 'toy-exchange-evaluator' ); ?></strong>
                        <p><?php _e( 'Previously built or played with', 'toy-exchange-evaluator' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Section (Toggle Switches) -->
        <div class="tee-details-box">
            <label class="tee-group-label"><?php _e( 'Set Details', 'toy-exchange-evaluator' ); ?></label>
            
            <div class="tee-detail-row" id="row-minifigs">
                <div class="tee-detail-info">
                    <strong><?php _e( 'Has All Minifigures', 'toy-exchange-evaluator' ); ?></strong>
                    <p><?php _e( 'All original minifigures included', 'toy-exchange-evaluator' ); ?></p>
                </div>
                <label class="tee-switch">
                    <input type="checkbox" id="tee-check-minifigs" checked>
                    <span class="tee-slider"></span>
                </label>
            </div>

            <div class="tee-detail-row" id="row-parts">
                <div class="tee-detail-info">
                    <strong><?php _e( 'Has All Parts/Pieces', 'toy-exchange-evaluator' ); ?></strong>
                    <p><?php _e( 'All pieces included', 'toy-exchange-evaluator' ); ?></p>
                </div>
                <label class="tee-switch">
                    <input type="checkbox" id="tee-check-parts" checked>
                    <span class="tee-slider"></span>
                </label>
            </div>


            <div class="tee-detail-row" id="row-box">
                <div class="tee-detail-info">
                    <strong><?php _e( 'Has Original Box', 'toy-exchange-evaluator' ); ?></strong>
                    <p><?php _e( 'Original packaging included', 'toy-exchange-evaluator' ); ?></p>
                </div>
                <label class="tee-switch">
                    <input type="checkbox" id="tee-check-box" checked>
                    <span class="tee-slider"></span>
                </label>
            </div>

            <div class="tee-detail-row" id="row-instructions">
                <div class="tee-detail-info">
                    <strong><?php _e( 'Has Instructions', 'toy-exchange-evaluator' ); ?></strong>
                    <p><?php _e( 'Building instructions included', 'toy-exchange-evaluator' ); ?></p>
                </div>
                <label class="tee-switch">
                    <input type="checkbox" id="tee-check-instructions" checked>
                    <span class="tee-slider"></span>
                </label>
            </div>

            <div class="tee-detail-row" id="row-assembled" style="display:none;">
                <div class="tee-detail-info">
                    <strong><?php _e( 'Is Set Assembled?', 'toy-exchange-evaluator' ); ?></strong>
                    <p><?php _e( 'Set is already built/assembled', 'toy-exchange-evaluator' ); ?></p>
                </div>
                <label class="tee-switch">
                    <input type="checkbox" id="tee-check-assembled">
                    <span class="tee-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- Minfigure Checklist (Modal style or expanded) -->
    <div id="tee-minifigs-ui" class="tee-card" style="display:none; margin-top:20px;">
        <h3><?php _e( 'Confirm Minifigures', 'toy-exchange-evaluator' ); ?></h3>
        <p><?php _e( 'Uncheck any missing minifigures:', 'toy-exchange-evaluator' ); ?></p>
        <div id="tee-minifigs-list" class="tee-minifigs-grid"></div>
    </div>

    <!-- Result Banner -->
    <div id="tee-result-ui" class="tee-result-banner" style="display:none;">
        <div class="tee-res-left">
            <h4 id="tee-res-name">Assembly Square</h4>
            <p id="tee-res-id">Set #10255</p>
            <div id="tee-res-tags" class="tee-tags">
                <!-- Tags added dynamically -->
            </div>
        </div>
        <div class="tee-res-right">
            <div class="tee-offer-label"><?php _e( 'Our Offer', 'toy-exchange-evaluator' ); ?></div>
            <div class="tee-offer-price" id="tee-final-price">£134.00</div>
            <button id="tee-add-to-cart" class="tee-btn-green">
                <span class="dashicons dashicons-cart"></span> <?php _e( 'Accept Offer', 'toy-exchange-evaluator' ); ?>
            </button>
        </div>
    </div>
</div>
