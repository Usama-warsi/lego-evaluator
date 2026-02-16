<?php
/**
 * LEGO Evaluator Form Template - Redesigned
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="tee-evaluator-container" class="tee-redesign-wrap">
    <!-- Sticky Valuation Bar -->
    <div id="tee-sticky-bar" class="tee-sticky-bar" style="display:none;">
        <div class="tee-sticky-content">
            <div class="tee-sticky-item">
                <span class="tee-label"><?php _e( 'Current Set:', 'toy-exchange-evaluator' ); ?></span>
                <span class="tee-val" id="tee-sticky-current">£0.00</span>
            </div>
            <div class="tee-sticky-item">
                <span class="tee-label"><?php _e( 'Total Valuation:', 'toy-exchange-evaluator' ); ?></span>
                <span class="tee-val" id="tee-sticky-total">£0.00</span>
            </div>
            <div id="tee-weight-limit-msg" class="tee-weight-warning" style="display:none;">
                <?php _e( 'Max box weight reached!', 'toy-exchange-evaluator' ); ?>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="tee-card tee-search-card">
        <h3><?php _e( 'Enter a LEGO Set number to get your valuation', 'toy-exchange-evaluator' ); ?></h3>
        <p class="tee-search-desc"><?php _e( 'Each box sent to us can weigh up to 18KG, please add items to your quote until your shipping box is full, or the max weight is achieved - our system will notify you when the weight limit has been met.', 'toy-exchange-evaluator' ); ?></p>
        <div class="tee-input-group">
            <input type="text" id="tee-set-number" placeholder="<?php _e( 'Enter LEGO Set number', 'toy-exchange-evaluator' ); ?>" class="tee-input">
            <button id="tee-search-set" class="tee-btn-dark">
                <span class="dashicons dashicons-search"></span> <?php _e( 'Search', 'toy-exchange-evaluator' ); ?>
            </button>
        </div>
        <div id="tee-set-preview" class="tee-set-preview" style="display:none; margin-top:20px;">
            <img id="tee-set-image-thumb" src="" alt="Set Image" style="max-width:150px; border-radius:8px; border:1px solid #ebedf0;">
            <p id="tee-set-name-preview" style="font-weight:700; margin:10px 0 0 0;"></p>
        </div>
        <div id="tee-search-error" class="tee-error" style="display:none;"></div>
        <div id="tee-loading" class="tee-loading" style="display:none; font-size: 1.2em; font-weight: 700;">
            <span class="tee-spinner"></span> <?php _e( 'Retrieving your valuation...', 'toy-exchange-evaluator' ); ?>
        </div>
    </div>

    <!-- Condition & Details Section -->
    <div id="tee-main-ui" class="tee-card tee-main-card" style="display:none;">
        <div class="tee-section-header">
            <h3><?php _e( 'Set Condition', 'toy-exchange-evaluator' ); ?></h3>
        </div>

        <div class="tee-form-group">
            <div class="tee-condition-grid dual-grid">
                <div class="tee-cond-card" data-cond="new">
                    <div class="tee-radio-circle"></div>
                    <div class="tee-cond-content">
                        <strong>
                            <span class="tee-icon">📦</span> <?php _e( 'New', 'toy-exchange-evaluator' ); ?>
                            <span class="tee-info-icon">i
                                <span class="tee-tooltip-text"><?php _e( 'Unopened or brand new condition', 'toy-exchange-evaluator' ); ?></span>
                            </span>
                        </strong>
                    </div>
                </div>
                <div class="tee-cond-card active" data-cond="used">
                    <div class="tee-radio-circle"></div>
                    <div class="tee-cond-content">
                        <strong>
                            <span class="tee-icon">🧩</span> <?php _e( 'Used', 'toy-exchange-evaluator' ); ?>
                            <span class="tee-info-icon">i
                                <span class="tee-tooltip-text"><?php _e( 'Previously built or played with', 'toy-exchange-evaluator' ); ?></span>
                            </span>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Questions Holder -->
        <div id="tee-dynamic-questions" class="tee-questions-box">
            <!-- Questions will be injected here via JS -->
        </div>

        <div id="tee-minifigs-ui" class="tee-questions-box" style="display:none;">
            <h3><?php _e( 'Minifigures', 'toy-exchange-evaluator' ); ?></h3>
            <p id="minifig-instruction-text"><?php _e( 'Please verify which minifigures are present:', 'toy-exchange-evaluator' ); ?></p>
            <div id="tee-minifigs-list" class="tee-minifigs-grid"></div>
        </div>
    </div>

    <!-- Result Banner -->
    <div id="tee-result-ui" class="tee-result-banner" style="display:none;">
        <div class="tee-res-left">
            <h4 id="tee-res-name"></h4>
            <p id="tee-res-id"></p>
            <div id="tee-res-tags" class="tee-tags"></div>
        </div>
        <div class="tee-res-right">
            <div class="tee-offer-label"><?php _e( 'Our Offer', 'toy-exchange-evaluator' ); ?></div>
            <div class="tee-offer-price" id="tee-final-price">£0.00</div>
            
            <div id="tee-rejection-msg" style="display:none;">
                <p class="tee-error"><?php _e( 'Unfortunately we would not accept this as a set, however we do buy Mixed Lego by weight.', 'toy-exchange-evaluator' ); ?></p>
                <a href="#" id="tee-rejection-btn" class="tee-btn-dark"><?php _e( 'Sell your LEGO online - £4.25 PER KG paid', 'toy-exchange-evaluator' ); ?></a>
            </div>

            <div id="tee-weight-error-msg" style="display:none;">
                <p class="tee-error"><?php _e( 'Maximum box weight reached (18KG). Please checkout these items before adding more.', 'toy-exchange-evaluator' ); ?></p>
            </div>

            <button id="tee-accept-set" class="tee-btn-green">
                <span class="dashicons dashicons-yes"></span> <?php _e( 'Accept Evaluation', 'toy-exchange-evaluator' ); ?>
            </button>
        </div>
    </div>

    <!-- Agreement List Section -->
    <div id="tee-agreement-list-wrap" class="tee-card" style="display:none; margin-top:20px;">
        <h3><?php _e( 'Your Evaluation List', 'toy-exchange-evaluator' ); ?></h3>
        <div id="tee-agreement-items" class="tee-agreement-items">
            <!-- Evaluated items appear here -->
        </div>
        <div class="tee-agreement-footer">
            <div class="tee-total-summary">
                <strong><?php _e( 'Total Offer:', 'toy-exchange-evaluator' ); ?> <span id="tee-agreement-total">£0.00</span></strong>
                <p><?php _e( 'Total Weight:', 'toy-exchange-evaluator' ); ?> <span id="tee-agreement-weight">0</span> KG</p>
            </div>
            <button id="tee-add-all-to-cart" class="tee-btn-green tee-btn-large">
                <span class="dashicons dashicons-cart"></span> <?php _e( 'Add All to Basket', 'toy-exchange-evaluator' ); ?>
            </button>
        </div>
    </div>
</div>

