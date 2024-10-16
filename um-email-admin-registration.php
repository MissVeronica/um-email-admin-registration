<?php
/**
 * Plugin Name:     Ultimate Member - Admin User Registration Notification Email
 * Description:     Extension to Ultimate Member to replace the WP new user email with an UM Notification email when Admin is doing the User Registration.
 * Version:         2.0.1
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Plugin URI:      https://github.com/MissVeronica/um-email-admin-registration
 * Update URI:      https://github.com/MissVeronica/um-email-admin-registration
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.8
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Admin_Registration_Notification_Email {

    public $slug = 'notification_admin_registration_email';

	public function __construct() {

        if ( is_admin()) {

            add_filter( 'um_admin_settings_email_section_fields', array( $this, 'um_admin_settings_email_section_email_admin_user' ), 10, 2 );
            add_filter( 'um_email_notifications',                 array( $this, 'um_email_notifications_admin_registration' ), 100, 1 );
            add_filter( 'wp_new_user_notification_email',         array( $this, 'wp_new_user_notification_um_email' ), 10, 3 );
            add_action( 'um_registration_complete',               array( $this, 'um_submitted_admin_registration_wp' ), 9, 2 );
        }

        add_filter( 'wp_send_new_user_notification_to_admin', array( $this, 'um_wp_send_notification_to_admin' ), 10, 2 );
        add_filter( 'wp_send_new_user_notification_to_user',  array( $this, 'um_wp_send_notification_to_user' ), 10, 2 );

        define( 'Plugin_Path_EAR', plugin_dir_path( __FILE__ ) );
    }

    public function wp_new_user_notification_um_email( $wp_new_user_notification_email, $user, $blogname ) {

        $template = $this->slug;

        $headers = 'From: '. stripslashes( UM()->options()->get( 'mail_from' ) ) .' <'. UM()->options()->get( 'mail_from_addr' ) .'>' . "\r\n";

        if ( UM()->options()->get( 'email_html' ) ) {
            $headers .= "Content-Type: text/html\r\n";

        } else {
            $headers .= "Content-Type: text/plain\r\n";
        }

        um_fetch_user( $user->ID );
        $args = array();

        add_filter( 'um_template_tags_patterns_hook', array( UM()->mail(), 'add_placeholder' ) );
        add_filter( 'um_template_tags_replaces_hook', array( UM()->mail(), 'add_replace_placeholder' ) );

        $subject = apply_filters( 'um_email_send_subject', UM()->options()->get( $template . '_sub' ), $template );
        $subject = wp_unslash( um_convert_tags( $subject , $args ) );
        $subject = html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' );

        $wp_new_user_notification_email['to'] = $user->user_email;
        $wp_new_user_notification_email['headers'] = $headers;
        $wp_new_user_notification_email['subject'] = $subject;
        $wp_new_user_notification_email['message'] = UM()->mail()->prepare_template( $template, $args );

        return $wp_new_user_notification_email;
    }

    public function um_submitted_admin_registration_wp( $user_id, $args ) {

        $form_id = sanitize_text_field( UM()->options()->get( 'um_wp_send_notification_form_id' ));

        if ( ! empty( $form_id ) && $form_id != '0000' ) {

            $submitted = array();

            $submitted['timestamp']  = current_time( 'timestamp' );
            $submitted['form_id']    = $form_id;
            $submitted['user_login'] = $args['user_login'];
            $submitted['email']      = $args['email'];
            $submitted['first_name'] = $args['first_name'];
            $submitted['last_name']  = $args['last_name'];
            $submitted['url']        = $args['url'];
            $submitted['role']       = $args['role'];
            $submitted['createuser'] = $args['createuser'];

            update_user_meta( $user_id, 'submitted', $submitted );
            update_user_meta( $user_id, 'timestamp', $submitted['timestamp'] );
        }
    }

    public function um_wp_send_notification_to_admin( $send_notification_to_admin, $user ) {

        if ( UM()->options()->get( 'um_wp_send_notification_to_admin' ) == 1 ) {
            $send_notification_to_admin = false;
        }

        return $send_notification_to_admin;
    }

    public function um_wp_send_notification_to_user( $send_notification_to_user, $user ) {

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 ) {
            $send_notification_to_user = true;
        }

        return $send_notification_to_user;
    }

    public function um_admin_settings_email_section_email_admin_user( $section_fields, $email_key ) { 

        if ( $this->slug == $email_key ) {

            $um_profile_forms = get_posts( array(   'meta_key'    => '_um_mode',
                                                    'meta_value'  => 'register',
                                                    'numberposts' => -1,
                                                    'post_type'   => 'um_form',
                                                    'post_status' => 'publish'
                                                ));

            $profile_forms['0000'] = esc_html__( 'No Registration Form', 'ultimate-member' );
            foreach( $um_profile_forms as $um_form ) {
                $profile_forms[$um_form->ID] = $um_form->post_title;
            }

            $section_fields[] = array(
                            'id'             => 'um_wp_send_notification_to_admin',
                            'type'           => 'checkbox',
                            'label'          => esc_html__( 'WP Registration Notification Email to Admin', 'ultimate-member' ),
                            'checkbox_label' => esc_html__( 'Tick to deactivate the short WP Registration Notification Email to Admin.', 'ultimate-member' ),
                            'conditional'    => array( $this->slug . '_on', '=', 1 ),
                        );

            $section_fields[] = array(
                            'id'             => 'um_wp_send_notification_form_id',
                            'type'           => 'select',
                            'size'           => 'small',
                            'options'        => $profile_forms,
                            'label'          => esc_html__( 'UM Administration Registration Form', 'ultimate-member' ),
                            'description'    => esc_html__( 'Design an UM Registration Form for updating the WP All Users "Info" popup. Disable the update use "No Profile form".', 'ultimate-member' ),
                            'conditional'    => array( $this->slug . '_on', '=', 1 ),
                        );
        }

        return $section_fields;
    }

    public function um_email_notifications_admin_registration( $um_emails ) {

        $custom_email = array( $this->slug => 
                                    array(
                                        'key'			 => $this->slug,
                                        'title'			 => esc_html__( 'Profile Created by Admin Email', 'ultimate-member' ),
                                        'subject'		 => 'Profile {username} is created by Admin',
                                        'body'			 => '',
                                        'description'    => esc_html__( 'To send a custom email to the user when profile is created by the site Administrator', 'ultimate-member' ),
                                        'recipient'		 => 'user',
                                        'default_active' => true
                                    )
                            );

        if ( UM()->options()->get( $this->slug . '_on' ) === '' ) {

            $email_on = empty( $custom_email['default_active'] ) ? 0 : 1;
            UM()->options()->update( $this->slug . '_on', $email_on );
        }

        if ( UM()->options()->get( $this->slug . '_sub' ) === '' ) {

            UM()->options()->update( $this->slug . '_sub', $custom_email['subject'] );
        }        
    
        $located = UM()->mail()->locate_template( $this->slug );

        if ( ! is_file( $located ) || filesize( $located ) == 0 ) {
            $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/' . $this->slug . '.php' );
        }

        clearstatcache();
        if ( ! file_exists( $located ) || filesize( $located ) == 0 ) {

            wp_mkdir_p( dirname( $located ) );

            $email_source = file_get_contents( Plugin_Path_EAR . $this->slug . '.php' );
            file_put_contents( $located, $email_source );

            if ( ! file_exists( $located ) ) {
                file_put_contents( um_path . 'templates/email/' . $this->slug . '.php', $email_source );
            }
        }

        return array_merge( $um_emails, $custom_email );
    }
}


new UM_Admin_Registration_Notification_Email();
