<?php
/*
Plugin Name: MIF Multisite Themes and Plugins Manager
Plugin URI: https://github.com/alexey-sergeev/mif-multisite-themes-and-plugins-manager
Description: Простой плагин для просмотра используемых тем и плагинов на сайтах сети WordPress
Author: Alexey N. Sergeev
Version: 1.0
Author URI: https://github.com/alexey-sergeev
Multisite: true;
*/

defined( 'ABSPATH' ) || exit;

class MIF_Multisite_TAP_Manager {
    
    function __construct() 
    {
        if ( ! is_multisite() ) return;

        add_action( 'network_admin_menu', array( $this, 'register_menu_page' ) );
    }

    function register_menu_page()
    {
        add_submenu_page( 'network-tools', __( 'Themes and Plugins Manager', 'mu-manager' ), __( 'Themes and Plugins Manager', 'mu-manager' ), 'manage_options', 'multisite-tap-manager', array( $this, 'sites_page' ) );
        wp_register_style( 'multisite-tap-manager-style', plugins_url( '/styles.css', __FILE__ ) );
        wp_enqueue_style( 'multisite-tap-manager-style' );  
    }

    function sites_page()
    {
        $out = '<h1>' . __( 'Multisite Themes and Plugins Manager', 'mu-tap-manager' ) . '</h1>';
        $out .= '<p>' . __( 'This page shows statistics on the use of themes and plug-ins on sites WordPress Multisite', 'mu-tap-manager' ) . '</p>';

        $out .= '<table class="widefat striped">
        <thead><tr>
        <th>' . __( 'Site', 'mu-tap-manager' ) . '</th>
        <th>' . __( 'ID', 'mu-tap-manager' ) . '</th>
        <th>' . __( 'Themes', 'mu-tap-manager' ) . '</th>
        <th>' . __( 'Plugins', 'mu-tap-manager' ) . '</th>
        </tr></thead><tbody>';

    	$network_plugins = $this->network_plugins();
        $out .= '<tr><td><span class="network">' . __( 'Network Plugins', 'mu-tap-manager' ) . '</span></td><td>&mdash;</td><td>&mdash;</td><td>' . $network_plugins . '</td></tr>';

        $sites = get_sites();

        foreach ( $sites as $site ) {

            $permalink = $this->permalink( $site );
            $themes = $this->themes( $site );
            $plugins = $this->plugins( $site );
            $out .= '<tr><td>' . $permalink . '</td><td>' . $site->blog_id . '</td><td>' . $themes . '</td><td>' . $plugins . '</td></tr>';

        }
        
        $unclaimed_plugins = $this->unclaimed_plugins();
        $unclaimed_themes = $this->unclaimed_themes();
        $out .= '<tr><td><span class="unclamed">' . __( 'Unclaimed Themes and Plugins', 'mu-tap-manager' ) . '</span></td><td>&mdash;</td><td>' . $unclaimed_themes . '</td><td>' . $unclaimed_plugins . '</td></tr>';
        
        
        
        
        // p($sites);



        $out .= '</table>';

        echo $out;
    }

    protected function permalink( $site )
    {
        return '<a href="' . get_site_url( $site->blog_id ) . '">' . $site->domain . $site->path . '</a>';
    }


    protected function themes( $site )
    {
        $themes = $this->get_themes( $site->blog_id );
        return implode( '<br />', $themes );
    }


    protected function get_themes( $site_id ) 
    {

        if ( $data = wp_cache_get( $site_id, 'mm_tap_manager_get_themes') ) return $data;

        global $wpdb;

        $prefix = $wpdb->get_blog_prefix( $site_id );
        $options_table = $prefix . 'options';

        $template_data = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $options_table WHERE option_name = %s LIMIT 1", 'template' ) );
        $stylesheet_data = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $options_table WHERE option_name = %s LIMIT 1", 'stylesheet' ) );

        $template = $template_data->option_value;
        $stylesheet = $stylesheet_data->option_value;
        
        $data = array();
        $data[] = $template;

        if ( $template != $stylesheet ) $data[] = $stylesheet;

        wp_cache_add( $site_id, $data, 'mm_tap_manager_get_themes' );

        return $data;
    }


    protected function unclaimed_themes()
    {
        $themes = $this->get_unclaimed_themes();
        return implode( '<br />', $themes );
    }


    protected function get_unclaimed_themes()
    {
        if ( $data = wp_cache_get( 'mm_tap_manager_get_unclaimed_themes' ) ) return $data;

        $all_themes_raw = wp_get_themes();

        $all_themes_index = array();
        foreach ( (array) $all_themes_raw as $key => $item ) $all_themes_index[$key] = 0;

        $sites = get_sites();
        foreach ( $sites as $site ) {
            $themes = $this->get_themes( $site->blog_id );
            foreach ( (array) $themes as $theme ) $all_themes_index[$theme]++;
        }

        $data = array();
        foreach ( $all_themes_index as $key => $value ) if ( $value == 0 ) $data[] = $key;

        wp_cache_add( 'mm_tap_manager_get_unclaimed_themes', $data );

        return $data;
    }


    protected function unclaimed_plugins()
    {
        $plugins = $this->get_unclaimed_plugins() ;
        return $this->plugins_to_str( $plugins );
    }


    protected function get_unclaimed_plugins()
    {
        if ( $data = wp_cache_get( 'mm_tap_manager_get_unclaimed_plugins' ) ) return $data;


        $all_plugins = get_plugins();

        $all_plugins_index = array();
        foreach ( (array) $all_plugins as $key => $value ) $all_plugins_index[$key] = 0;

        $sites = get_sites();
        foreach ( $sites as $site ) {
            $plugins = $this->get_plugins( $site->blog_id );
            foreach ( (array) $plugins as $plugin ) $all_plugins_index[$plugin['plugin']]++;
        }

        $plugins = $this->get_network_plugins() ;
        foreach ( (array) $plugins as $plugin ) $all_plugins_index[$plugin['plugin']]++;

        $unclaimed_plugins = array();
        foreach ( $all_plugins_index as $key => $value ) if ( $value == 0 ) $unclaimed_plugins[] = $key;

        $data = array();
        foreach ( (array) $unclaimed_plugins as $key => $value ) $data[] = $this->get_detail_plugin_data( $value );

        wp_cache_add( 'mm_tap_manager_get_unclaimed_plugins', $data );

        return $data;
    }


    protected function network_plugins()
    {
        $plugins = $this->get_network_plugins() ;
        return $this->plugins_to_str( $plugins );
    }


    protected function get_network_plugins()
    {
        if ( $data = wp_cache_get( 'mm_tap_manager_get_network_plugins' ) ) return $data;

    	$network_plugins = get_site_option( 'active_sitewide_plugins');

        $data = array();
        foreach ( (array) $network_plugins as $key => $value ) $data[] = $this->get_detail_plugin_data( $key );

        wp_cache_add( 'mm_tap_manager_get_network_plugins', $data );

        return $data;
    }


    protected function plugins( $site )
    {
        $plugins = $this->get_plugins( $site->blog_id ) ;
        return $this->plugins_to_str( $plugins );
    }


    protected function get_plugins( $site_id ) 
    {

        if ( $data = wp_cache_get( $site_id, 'mm_tap_manager_get_plugins') ) return $data;

        global $wpdb;

        $prefix = $wpdb->get_blog_prefix( $site_id );
        $options_table = $prefix . 'options';

        $raw_data = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $options_table WHERE option_name = %s LIMIT 1", 'active_plugins' ) );
        $active_plugins = unserialize( $raw_data->option_value );
        
        $data = array();
        foreach ( (array) $active_plugins as $key => $value ) $data[] = $this->get_detail_plugin_data( $value );

        wp_cache_add( $site_id, $data, 'mm_tap_manager_get_plugins' );

        return $data;
    }


    protected function get_detail_plugin_data( $plugin )
    {
        $arr['plugin'] = $plugin;
        $arr['clean_name'] = $this->get_clean_plugin_name( $plugin );
        $arr['validity'] = ( is_wp_error( validate_plugin( $plugin ) ) ) ? false : true;

        $before = ( $arr['validity'] ) ? '' : '<span class="missing" title="' . __( 'Plugin is missing', 'mu-tap-manager' ) . '">';
        $after = ( $arr['validity'] ) ? '' : '</span>';
        $arr['string'] = $before . $arr['clean_name'] . $after;

        return $arr;
    }


    protected function get_clean_plugin_name( $str )
    {
        $arr = explode( '/', $str );
        return $arr[0];
    }


    protected function plugins_to_str( $plugins )
    {
        $arr = array();
        foreach ( (array) $plugins as $data ) if ( isset($data['string']) ) $arr[] = $data['string'];
        return implode( '<br />', $arr );
    }



}

new MIF_Multisite_TAP_Manager();


function p( $data )
{
    print_r( '<pre>' );
    print_r( $data );
    print_r( '</pre>' );
}

?>