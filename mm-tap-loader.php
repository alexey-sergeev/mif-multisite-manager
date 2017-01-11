<?php
/*
Plugin Name: MIF Multisite Themes and Plugins Manager
Plugin URI: https://github.com/alexey-sergeev/mif-multisite-themes-and-plugins-manager
Description: Простой плагин для просмотра используемых тем и плагинов на сайтах сети WordPress
Author: Alexey N. Sergeev
Version: 1.0
Author URI: https://github.com/alexey-sergeev
*/

defined( 'ABSPATH' ) || exit;

class MIF_Multisite_TAP_Manager {

    function __construct() 
    {

        add_action( 'network_admin_menu', array( &$this, 'register_menu_page' ) );

    }


    function register_menu_page()
    {

        add_submenu_page( 'network-tools', __( 'Multisite Manager', 'mu-manager' ), __( 'Multisite Manager', 'mu-manager' ), 'manage_options', 'multisite-manager', array( &$this, 'plugin_page' ) );
        wp_register_style( 'multisite-manager-style', plugins_url( '/style.css', __FILE__ ) );
        wp_enqueue_style( 'multisite-manager-style' );  

    }

    function plugin_page()
    {
        $out = '<h1>' . __( 'Multisite Manager', 'mu-manager' ) . '</h1>';

        $out .= $this -> get_sites();

        echo $out;

    }


    function get_sites()
    {

        $out = '123';

        return $out;
    }











}

new MIF_Multisite_TAP_Manager();









// add_action( 'network_admin_menu', 'register_mm_menu_page', 100 );

// function register_mm_menu_page()
// {

//     add_submenu_page( 'network-tools', __( 'Multisite Manager', 'mu-manager' ), __( 'Multisite Manager', 'mu-manager' ), 'manage_options', 'multisite-manager', 'mm_menu_page' );
//     wp_register_style( 'multisite-manager-style', plugins_url( '/style.css', __FILE__ ) );
//     wp_enqueue_style( 'multisite-manager-style' );  

// }

// function mm_menu_page()
// {
//     $out = '<h1>Статистика пользователей</h1>';




//     echo $out;

// }


?>
