<?php

add_shortcode('shipment_grid', 'shipment_grid_shortcode');

function shipment_grid_shortcode() {

    $query = new WP_Query([
        'post_type' => 'shipment',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);

    ob_start();

    echo '<div class="shipment-grid">';

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();

            $tracking = get_post_meta(get_the_ID(), '_shipment_tracking_number', true);
            $status   = get_post_meta(get_the_ID(), '_shipment_status', true);

            echo '<div class="shipment-card">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<p><strong>Tracking:</strong> ' . esc_html($tracking) . '</p>';
            echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
            echo '<a href="' . get_permalink() . '" class="shipment-btn">View Details</a>';
            echo '</div>';

        endwhile;
    else :
        echo '<p>No shipments found.</p>';
    endif;

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}
