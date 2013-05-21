<?php

/**
 * Plugin Name: Relief Splash Page
 * Plugin URI:  https://github.com/jchristopher/relief-splash-page
 * Description: Add a splash page to encourage donations for disaster relief
 * Author:      Jonathan Christopher
 * Author URI:  http://jchristopher.me
 * Version:     1.0
 * Text Domain: rsp
 * Domain Path: languages
 *
 *    =======================================================================================
 *    = This plugin is facilitated by the work of Yaron Schoen and Noah Stokes              =
 *    = More information at https://github.com/noahstokes/Sandy-Relief/tree/Moore-Relief    =
 *    =======================================================================================
 *
 * Relief Splash Page is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Relief Splash Page is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Relief Splash Page. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Relief Splash Page
 * @author Jonathan Christopher
 * @version 1.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class ReliefSplashPage
{

    private $url;
    private $dir;
    private $applicable;
    private $cookie = 'wp_rsp_viewed';
    private $prefix = 'rsp';

    function __construct()
    {
        $this->dir = plugin_dir_path( __FILE__ );
        $this->url = plugin_dir_url( __FILE__ );

        $this->maybe_cookie();

        $this->applicable = ( isset( $_GET['wprsp'] ) || isset( $_COOKIE[$this->cookie] ) ) ? false : true;

        add_filter( 'template_redirect',    array( $this, 'maybe_hijack_request' ) );
        add_action( 'admin_init',           array( $this, 'wp_admin_init' ) );
        add_action( 'admin_menu',           array( $this, 'admin_page' ) );
    }


    function maybe_cookie()
    {
        if( isset( $_GET['wprsp'] ) && !isset( $_COOKIE[$this->cookie] ) )
            setcookie( $this->cookie , 0, time() + 1209600, COOKIEPATH, COOKIE_DOMAIN, false );
    }


    function maybe_hijack_request()
    {
        if( $this->applicable )
        {
            $currentURL     = $this->getCurrentUrl();
            $settings       = get_option( $this->prefix . 'settings' );
            $saved_message  = sanitize_text_field( $settings['message'] );
            $varflag        = strpos( $currentURL, '?' ) ? '&amp;' : '?';
            ?>
            <!DOCTYPE html>
            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php _e( 'Donate to the American Red Cross', 'rsp' );?></title>
                <link rel="stylesheet" href="<?php echo trailingslashit( $this->url ); ?>assets/styles/style.css">
            </head>
            <body id="red-cross" >
                <div class="page center_text">
                    <div><img src="<?php echo trailingslashit( $this->url ); ?>assets/images/redcross.jpg" alt="Red Cross Logo" /></div>
                    <p><?php echo $saved_message; ?></p>
                    <a href="http://www.redcross.org/charitable-donations" class="donate"><?php _e( 'Donate', 'rsp' ); ?></a>
                    <p class="small"><?php _e( 'Or text REDCROSS to 90999 to donate $10', 'rsp' ); ?></p>
                    <hr>
                    <a href="<?php echo $currentURL . $varflag; ?>wprsp" class="small text-link"><?php _e( 'Continue to' , 'rsp' ); ?> <?php echo get_bloginfo( 'name' ); ?></a>
                </div>
            </body>
            </html>
            <?php die();
        }
    }


    function admin_page()
    {
        add_options_page( 'Settings', __( 'Relief Splash Page', 'rsp' ), 'manage_options', 'relief-splash-page', array( $this, 'options_page' ) );
    }


    function options_page()
    { ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"><br /></div>
                <h2><?php _e( 'Relief Splash Page Options', 'rsp' ); ?></h2>
                <form action="options.php" method="post">
                    <div id="poststuff" class="metabox-holder">
                        <?php settings_fields( $this->prefix . 'settings' ); ?>
                        <?php do_settings_sections( $this->prefix . 'settings' ); ?>
                    </div>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'rsp' ); ?>" />
                    </p>
                </form>
            </div>
        <?php
    }


    function wp_admin_init()
    {
        // flag our settings
        register_setting(
            $this->prefix . 'settings',
            $this->prefix . 'settings',
            array( $this, 'validate_settings' )
        );

        add_settings_section(
            $this->prefix . 'options',
            __( 'Relief Splash Page Settings', 'rsp' ),
            array( $this, 'edit_settings' ),
            $this->prefix . 'settings'
        );

        add_settings_field(
            $this->prefix . 'message',
            __( 'Message', 'rsp' ),
            array( $this, 'edit_option_message' ),
            $this->prefix . 'settings',
            $this->prefix . 'options'
        );
    }


    function edit_option_message()
    {
        $settings = get_option( $this->prefix . 'settings' );
        $saved_message = !empty( $settings['message'] ) ? sanitize_text_field( $settings['message'] ) : __( 'Help survivors. Donate to the American Red Cross.', 'rsp' ); ?>
        <input type="text" class="large-text" name="<?php echo $this->prefix; ?>settings[message]" value="<?php echo $saved_message; ?>" />
    <?php
    }


    function edit_settings()
    {

    }



    function validate_settings( $input )
    {
        $input['message'] = !empty( $input['message'] ) ? sanitize_text_field( $input['message'] ) : '';
        return $input;
    }


    function getCurrentUrl()
    {
        $pageURL = 'http';

        if( isset( $_SERVER["HTTPS"] ) )
            if( $_SERVER["HTTPS"] == "on" )
                $pageURL .= "s";

        $pageURL .= "://";
        if($_SERVER["SERVER_PORT"] != "80" )
        {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        }
        else
        {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        return $pageURL;

    }

}

new ReliefSplashPage();