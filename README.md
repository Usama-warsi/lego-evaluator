# Toy Exchange LEGO Evaluator

A custom WordPress plugin that allows users to evaluate their LEGO sets using the Bricklink API and seamlessly add the evaluated items to their WooCommerce cart with a custom price.

## Features

- **Bricklink API Integration**: Fetches real-time market data (New and Used price averages) for LEGO sets.
- **Dynamic Pricing**: Custom pricing tiers based on the set's market value.
- **Condition-Based Valuation**: Automatic deductions for missing parts, instructions, or damaged packaging.
- **Minifigure Breakdown**: Detects and evaluates individual minifigures within a set for accurate missing-item deductions.
- **WooCommerce Integration**: Adds evaluated sets to the cart with metadata and dynamically calculated prices.
- **Customizable UI**: Full control over brand colors, typography, and layout via admin settings.
- **Debug Mode**: JSON-based logging for API requests and responses.

## Installation

1. Upload the `lego-evaluator` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure **WooCommerce** is installed and active.

## Configuration

Navigate to **LEGO Evaluator** in the WordPress admin menu to configure the following tabs:

### 1. API Credentials
Enter your Bricklink API credentials (Consumer Key, Consumer Secret, Token Value, Token Secret). You can obtain these from the [Bricklink API setup](https://www.bricklink.com/v2/api/welcome.page).

### 2. Pricing Rules
Define percentage payouts based on market value ranges. You can set different percentages for:
- **New Sealed**
- **New Opened**
- **Used**

### 3. Condition Rules
Configure global deductions (in %) for:
- Damaged boxes
- Missing instructions
- Missing parts
- Missing minifigures (using a multiplier of market value)

### 4. UI Styling
Customize the appearance of the evaluator tool on your frontend:
- **Brand Colors**: Primary, Accent, Card Background.
- **Layout**: Container max-width and margins.
- **Typography**: Font family and text color.

### 5. General Settings
- **Evaluated Product**: Select a simple WooCommerce product that will serve as the "placeholder" for items added to the cart.
- **Debug Mode**: Enable this to log API traffic for troubleshooting.

## Usage

Use the following shortcode to display the LEGO Evaluator on any page or post:

`[lego_evaluator]`

## Troubleshooting

If evaluations aren't working as expected:
1. Enable **Debug Mode** in General Settings.
2. Check the **Logs** tab in the plugin settings to view raw API responses.
3. Ensure your Bricklink API credentials have correctly configured permissions.

## Requirements

- **PHP**: 7.4+
- **WordPress**: 5.8+
- **WooCommerce**: 5.0+
- **Bricklink API Access**
