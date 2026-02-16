# Toy Exchange LEGO Evaluator (v1.1.0)

A custom WordPress plugin that allows users to evaluate their LEGO sets using the Bricklink API and seamlessly add the evaluated items to their WooCommerce cart with a custom price. Version 1.1.0 introduces a granular valuation model including seals-based checks and weight-based payouts for incomplete sets.

## Author & Version
- **Author**: Muhammad Usama
- **Version**: 1.1.0

## Features

- **Bricklink API Integration**: Fetches real-time market data (New and Used price averages).
- **Advanced Valuation Flow**: Separate logic for New (Seals Intact/Broken), Used (Completeness Tiers), and Mixed LEGO.
- **Weight-Based Payouts**: Automatic calculation for incomplete or mixed sets based on KG rates.
- **Condition-Based Valuation**: Granular deductions for box condition (Like New, Fair, Bad), instructions, and build status (Built/Unbuilt).
- **Minifigure Breakdown**: Count-based valuation and deduction for individual minifigures.
- **WooCommerce Integration**: Adds evaluated sets to the cart with dynamically calculated prices.
- **Customizable UI**: Full control over brand colors, typography, and layout via admin settings.
- **Rejection/Redirect Flow**: Automatically redirects users to a custom URL if a set cannot be quoted.

## How to Use

### 1. Admin Configuration
Navigate to **LEGO Evaluator** in your WordPress dashboard to set up the plugin:
1.  **API Credentials**: Enter your Bricklink Consumer and Token details. Use the built-in tooltips for guidance.
2.  **Pricing Rules**: Define base payout percentages based on the market value of the set.
3.  **Condition Rules**: This is the heart of the v1.1.0 logic. Configure:
    - **Seals Intact**: Define payouts for Like New, Fair, and Bad box conditions.
    - **Seals Broken (New Open)**: Set a "Complete" percentage and a "Weight-based" rate for incomplete new sets.
    - **Used Tiers**: Define built/unbuilt payouts for 100% complete, 95%+ complete, and Mixed LEGO (<95%).
    - **Global Limits**: Set the **Max Box Weight** and the **Rejection Redirect URL**.
4.  **UI Styling**: Match the evaluator to your site's theme using brand colors and layout settings.
5.  **General**: Select the default WooCommerce product to use as the base for all quotes.

### 2. Frontend Placement
Insert the evaluator tool anywhere on your site using the shortcode:
`[lego_evaluator]`

### 3. Customer Flow
Once deployed, customers follow this flow:
1.  **Search**: Enter a LEGO set number (e.g., 10255).
2.  **Select Condition**: Choose between New (Seals Intact), New (Seals Broken), or Used.
3.  **Refine Details**: 
    - For New/Seals Intact: Choose box condition.
    - For Used/Open: Specify completeness (100%, 95%+, or Mixed) and built status.
    - **Minifigures**: Uncheck any missing minifigures.
4.  **Weight Detection**: For incomplete sets, users enter the weight (grams) for a KG-based quote.
5.  **Accept Offer**: The plugin calculates the offer and adds it to the cart with all metadata.

## Installation

1. Upload the `lego-evaluator` folder to `/wp-content/plugins/`.
2. Activate via the 'Plugins' menu.
3. Ensure **WooCommerce** is active.

## Troubleshooting

- **Debug Mode**: Enable in General Settings to log API traffic.
- **Logs Tab**: View raw Bricklink API responses in the settings panel.

## Requirements

- **PHP**: 7.4+
- **WordPress**: 5.8+
- **WooCommerce**: 5.0+
