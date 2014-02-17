<?php

class Welcome extends Dashboard_Plugin {
    function init() {
        add_action( 'quickstart_dashboard_setup', array( $this, 'dashboard_setup' ) );
    }
    
    function dashboard_setup() {
        wp_add_dashboard_widget( 'quickstart_dashboard_hello', $this->name(), array( $this, 'show' ) );
    }
    
    function name() {
        return __( 'Welcome', 'quickstart-dashboard' );
    }
    
    function show() {
        ?>
        <p><?php _e( 'Welcome to the VIP Quickstart Dashboard!', 'quickstart-dashboard' ); ?></p>

        <style>
            .vip-dashboard-widget-column {
                display: inline-block;
                width: 45%;
                margin-right: 25px;
            }

            .vip-dashboard-widget-column.last-widget-column {
                margin-right: 0;
            }
            .vip-dashboard-widget-column li {
                margin-bottom: 16px;
            }
            .vip-dashboard-widget-column a {
                display: block;
                font-size: 110%;
            }
            .vip-dashboard-widget-column .helpful-link-description {
                font-size: 80%;
                color: #888;
            }
        </style>

        <br />

        <h4><?php _e( 'Helpful Links' ); ?></h4>
        <div class="vip-dashboard-widget-column">
            <ul>
                <li>
                    <a href="http://lobby.vip.wordpress.com" target="_blank"><?php _e( 'VIP Lobby', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Important service updates', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://vip.wordpress.com/documentation/" target="_blank"><?php _e( 'VIP Documentation', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Coding for WordPress.com VIP', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://vip.wordpress.com/plugins/" target="_blank"><?php _e( 'VIP Plugins', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Available shared VIP plugins', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://wordpressvip.zendesk.com/" target="_blank"><?php _e( 'VIP Support Portal', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Your organization’s tickets', 'quickstart-dashboard' ); ?></span>
                </li>
            </ul>
        </div>

        <div class="vip-dashboard-widget-column last-widget-column">
            <ul>
                <li>
                    <a href="http://vip.wordpress.com/documentation/launch-checklist/" target="_blank"><?php _e( 'Launch Checklist', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Steps to launch', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://vip.wordpress.com/your-vip-toolbox/" target="_blank"><?php _e( 'Your VIP Toolbox', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Navigating VIP Tools', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://vip.wordpress.com/blog/" target="_blank"><?php _e( 'VIP News', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'New features, case studies', 'quickstart-dashboard' ); ?></span>
                </li>
                <li>
                    <a href="http://vip.wordpress.com/partners/" target="_blank"><?php _e( 'Featured Partners', 'quickstart-dashboard' ); ?></a>
                    <span class="helpful-link-description"><?php _e( 'Agencies and technology partners', 'quickstart-dashboard' ); ?></span>
                </li>
            </ul>
        </div>

        <?php
    }
}
