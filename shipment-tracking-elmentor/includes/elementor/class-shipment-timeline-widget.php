<?php
class Shipment_Timeline_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'shipment_timeline';
    }

    public function get_title() {
        return 'Shipment Timeline';
    }

    public function get_icon() {
        return 'eicon-time-line';
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
        $this->add_control(
            'show_message',
            [
                'label' => 'Show Message',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_address',
            [
                'label' => 'Show Address',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_date',
            [
                'label' => 'Show Date',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_status',
            [
                'label' => 'Show Status',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );


        $this->end_controls_section();

        $this->start_controls_section(
            'timeline_style_section',
            [
                'label' => __('Timeline Style', 'shipment-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'timeline_line_color',
            [
                'label' => __('Line Color', 'shipment-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1e3a8a',
            ]
        );

        $this->add_control(
            'timeline_dot_color',
            [
                'label' => __('Dot Color', 'shipment-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#2563eb',
            ]
        );

        $this->add_control(
            'timeline_dot_size',
            [
                'label' => __('Dot Size', 'shipment-manager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 14,
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {

        $settings = $this->get_settings_for_display();
        $route_points = get_post_meta(get_the_ID(), '_shipment_route_points', true);
        $tracking = get_post_meta(get_the_ID(), '_shipment_tracking_number', true);
        $status = get_post_meta(get_the_ID(), '_shipment_status', true);
        $total_time = get_post_meta(get_the_ID(), '_total_time', true);
        $courier_name = get_post_meta(get_the_ID(), '_courier_name', true);
        $courier_phone = get_post_meta(get_the_ID(), '_courier_phone', true);
        $courier_message = get_post_meta(get_the_ID(), '_courier_message', true);

        $line_color = $settings['timeline_line_color'] ?? '#ddd';
        $dot_color  = $settings['timeline_dot_color'] ?? '#0073aa';
        $dot_size   = $settings['timeline_dot_size']['size'] ?? 16;



        if (empty($route_points)) return; ?>

        <div class="shipment-top-info">
            <h3>Shipment Details</h3>
            <?php
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
            if ($settings['show_message'] === 'yes' && !empty(($courier_message))) {
                echo ' <p><strong>Message:</strong>'.esc_html($courier_message).'</p>';
            }
            ?>
        </div>
        <?php
        echo '<div class="shipment-timeline" style="
            --line-color: ' . esc_attr($line_color) . ';
            --dot-color: ' . esc_attr($dot_color) . ';
            --dot-size: ' . esc_attr($dot_size) . 'px;
        ">';
        foreach ($route_points as $index => $point) {

            echo '<div class="shipment-timeline-item timeline-item">';

            echo '<div class="timeline-dot"></div>';

            echo '<div class="timeline-content">';
            echo '<strong>' . esc_html($point['city']) . '</strong><br>';

            if ($settings['show_address'] === 'yes' && !empty($point['address'])) {
                echo esc_html($point['address']) . '<br>';
            }

            if ($settings['show_date'] === 'yes' && !empty($point['datetime'])) {
                echo date('d M Y H:i', strtotime($point['datetime'])) . '<br>';
            }

            if ($settings['show_status'] === 'yes' && !empty($point['status'])) {
                echo esc_html($point['status']);
            }

            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; ?>
        <style>
            .timeline-dot {
                position: absolute;
                top: 9px;
                left: calc(-1 * (var(--dot-size, 16px) / 2));
                width: var(--dot-size, 16px);
                height: var(--dot-size, 16px);
                background-color: var(--dot-color, #0073aa);
                border: 3px solid #fff;
                border-radius: 50%;
                z-index: 1;
                box-shadow: 0 0 0 2px var(--dot-color, #0073aa);
            }

            .timeline-item:not(:last-child)::after {
                content: '';
                position: absolute;
                top: calc(var(--dot-size, 16px) / 2);
                bottom: -25px;
                left: 0;
                width: 2px;
                background-color: var(--line-color, #ddd);
                z-index: 0;
            }
            .timeline-content {
                padding-left: 20px;
            }


        </style>

<?php
    }
}
