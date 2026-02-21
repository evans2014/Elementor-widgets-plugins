<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Shipment_Grid_Widget extends Widget_Base {

    public function get_name() {
        return 'shipment_grid';
    }

    public function get_title() {
        return 'Shipment Posts Grid';
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Content',
            ]
        );

        $this->add_control(
            'show_number',
            [
                'label' => 'Show Number',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_all_status',
            [
                'label' => 'Show Map Status',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_time',
            [
                'label' => 'Show Time',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_name',
            [
                'label' => 'Show name',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_phone',
            [
                'label' => 'Show Phone',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );


        $this->end_controls_section();

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Settings',
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => 'Posts Per Page',
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();

        $args = [
            'post_type' => 'shipment',
            'posts_per_page' => $settings['posts_per_page'],
        ];

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            echo '<p>No shipments found.</p>';
            return;
        }

        echo '<div class="shipment-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px;">';

        while ($query->have_posts()) {
            $query->the_post();

            $tracking = get_post_meta(get_the_ID(), '_shipment_tracking_number', true);
            $status = get_post_meta(get_the_ID(), '_shipment_status', true);
            $total_time = get_post_meta(get_the_ID(), '_total_time', true);
            $courier_name = get_post_meta(get_the_ID(), '_courier_name', true);
            $courier_phone = get_post_meta(get_the_ID(), '_courier_phone', true);
            $courier_message = get_post_meta(get_the_ID(), '_courier_message', true);

            echo '<div class="shipment-card" style="border:1px solid #ddd; padding:15px; border-radius:8px;">';
            echo '<h3>' . get_the_title() . '</h3>';

            if ($settings['show_number'] === 'yes' ) {
                echo ' <p><strong>Tracking:</strong>'.esc_html($tracking).'</p>';
            }
            if ($settings['show_all_status'] === 'yes' ) {
                echo ' <p><strong>Status:</strong>'.esc_html($status).'</p>';
            }
            if ($settings['show_time'] === 'yes' ) {
                echo ' <p><strong>Total Time:</strong>'.esc_html($total_time).'</p>';
            }
            if ($settings['show_name'] === 'yes' ) {
                echo ' <p><strong>Courier:</strong>'.esc_html($courier_name).'</p>';
            }
            if ($settings['show_phone'] === 'yes' ) {
                echo ' <p><strong>Phone:</strong>'.esc_html($courier_phone).'</p>';
            }
            echo '<a href="' . get_permalink() . '" class="button">View Shipment</a>';
            echo '</div>';
        }

        echo '</div>';

        wp_reset_postdata();
    }
}
