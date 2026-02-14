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
    ?>
    <div class="wrap">
        <h1>Shipment Tools</h1>

        <form method="post">
            <?php wp_nonce_field('shipment_tools_action', 'shipment_tools_nonce'); ?>

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
        </form>
    </div>
    <?php
}
