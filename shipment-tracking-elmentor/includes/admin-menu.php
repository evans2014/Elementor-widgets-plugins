<?php
add_action('admin_menu', 'shipment_admin_menu');

function shipment_admin_menu() {
    add_menu_page(
        'Shipment Tools',
        'Shipment Tools',
        'manage_options',
        'shipment-tools',
        'shipment_tools_page',
        'dashicons-location-alt',
        26
    );
}

function shipment_tools_page() {

    // Save Google API Key
    if (
        isset($_POST['shipment_tools_nonce']) &&
        wp_verify_nonce($_POST['shipment_tools_nonce'], 'shipment_tools_action')
    ) {

        if (isset($_POST['google_maps_api_key'])) {
            update_option(
                'shipment_google_maps_api',
                sanitize_text_field($_POST['google_maps_api_key'])
            );
        }
        // install/reset
    }

    $google_api_key = get_option('shipment_google_maps_api', '');
    ?>
    <div class="wrap">
        <h1>Shipment Tools</h1>
        <form method="post">
            <?php wp_nonce_field('shipment_tools_action', 'shipment_tools_nonce'); ?>
            <h2>Demo Data</h2>
            <p>
                <button name="install_demo" class="button button-primary">
                    Install Demo Data
                </button>
            </p>
            <p>
                <button name="reset_demo" class="button button-secondary">
                    Reset Demo Data
                </button>
            </p>
            <hr>
            <h2>Google Maps</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        Google Maps API Key <br>only for single shipment post
                    </th>
                    <td>
                        <input
                                type="text"
                                name="google_maps_api_key"
                                value="<?php echo esc_attr($google_api_key); ?>"
                                style="width: 420px;"
                                placeholder="AIza..."
                        />
                        <p class="description">
                            If empty, the plugin will fallback to OpenStreetMap (Leaflet).
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
            <div>
                <h3>Shortcode:</h3>
                <p>[shipment_search_form] - search from by tracking number</p>
                <p>[shipment_search]-to display the shipment search form anywhere.</p>
                <p>[shipment_grid] - to display the shipment post grid.</p>

            </div>
            <div>
                <h3>Elemntor widgets:</h3>
                <p>Single Shipment - display map </p>
                <p>Shipment post grid - for post grod</p>
                <p>Shipment timeline - display info and city timeline</p>

            </div>
        </form>
    </div>
    <?php
}