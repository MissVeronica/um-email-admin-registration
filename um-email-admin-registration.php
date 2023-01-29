<?php
/**
 * Plugin Name:     Ultimate Member - Notification Email Admin Registration
 * Description:     Extension to Ultimate Member to replace WP new user email with an UM Notification email when Admin is doing the Registration.
 * Version:         1.0.0 
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Admin_Registration_Notification_Email {


	public function __construct() {

        if( is_admin()) {

            add_filter( 'um_settings_structure',          array( $this, 'um_settings_structure_new_user_notification_email' ), 10, 1 );
            add_filter( 'um_email_notifications',         array( $this, 'um_email_notifications_admin_registration' ), 10, 1 );
            add_filter( 'wp_new_user_notification_email', array( $this, 'wp_new_user_notification_um_email' ), 10, 3 );
            add_action( 'um_registration_complete',       array( $this, 'um_submitted_admin_registration_wp' ), 9, 2 );
        }

        add_filter( 'wp_send_new_user_notification_to_admin', array( $this, 'um_wp_send_notification_to_admin' ), 10, 2 );
        add_filter( 'wp_send_new_user_notification_to_user',  array( $this, 'um_wp_send_notification_to_user' ), 10, 2 );
    }

    public function wp_new_user_notification_um_email( $wp_new_user_notification_email, $user, $blogname ) {

        $template = UM()->options()->get( 'um_new_admin_user_notification_email' );
        if ( $template != 'send_wp' ) {

            $headers = 'From: '. stripslashes( UM()->options()->get( 'mail_from' ) ) .' <'. UM()->options()->get( 'mail_from_addr' ) .'>' . "\r\n";
            if ( UM()->options()->get( 'email_html' ) ) {
                $headers .= "Content-Type: text/html\r\n";
            } else {
                $headers .= "Content-Type: text/plain\r\n";
            }

            $args = array();
            $subject = apply_filters( 'um_email_send_subject', UM()->options()->get( $template . '_sub' ), $template );
            $subject = wp_unslash( um_convert_tags( $subject , $args ) );
            $subject = html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' );
            
            $wp_new_user_notification_email['headers'] = $headers;
            $wp_new_user_notification_email['subject'] = $subject;
            $wp_new_user_notification_email['message'] = UM()->mail()->prepare_template( $template, $args );
        }

        return $wp_new_user_notification_email;
    }

    public function um_submitted_admin_registration_wp( $user_id, $args ) {

        $form_id = UM()->options()->get( 'um_wp_send_notification_form_id' );

        if ( ! empty( $form_id )) {

            $submitted = array();

            $submitted['timestamp'] = current_time( 'timestamp' );
            $submitted['form_id'] = $form_id;

            update_user_meta( $user_id, 'submitted', $submitted );
            update_user_meta( $user_id, 'timestamp', $submitted['timestamp'] );
        }
    }

    public function um_wp_send_notification_to_admin( $send_notification_to_admin, $user ) {

        $notification_to_admin = UM()->options()->get( 'um_wp_send_notification_to_admin' );
        if ( $notification_to_admin ) $send_notification_to_admin = false;

        return $send_notification_to_admin;
    }

    public function um_wp_send_notification_to_user( $send_notification_to_user, $user ) {

        $template = UM()->options()->get( 'um_new_admin_user_notification_email' );

        if( $template == 'none' ) {
            $send_notification_to_user = false;
        }

        return $send_notification_to_user;
    }

    public function um_settings_structure_new_user_notification_email( $settings_structure ) { 

        $email_notifications = UM()->config()->email_notifications;

        $notification_list = array( 'send_wp' => __( 'Send Default email: WP New User Registration', 'ultimate-member' ),
                                    'none'    => __( 'Deactivate email: WP New User Registration', 'ultimate-member' ) );

        $um_notifications = array(  'approved_email',
                                    'changedaccount_email',
                                    'changedpw_email',
                                    'checkmail_email', 
                                    'deletion_email',
                                    'inactive_email',
                                    'notification_deletion', 
                                    'notification_new_user',
                                    'notification_review', 
                                    'pending_email',
                                    'rejected_email',
                                    'resetpw_email',
                                    'welcome_email' );

        foreach ( $email_notifications as $key => $email_notification ) {

            if ( in_array( $key, $um_notifications )) {
                $notification_list[$key] = sprintf( __( 'Send UM: %s', 'ultimate-member' ), $email_notification['title'] );
            } else {
                $notification_list[$key] = sprintf( __( 'Send Custom: %s', 'ultimate-member' ), $email_notification['title'] );
            }
        }

        $settings_structure['email']['fields'][] = array(
                        'id'      => 'um_new_admin_user_notification_email',
                        'type'    => 'select',
                        'options' => $notification_list,
                        'label'   => __( 'Notification User Email at Admin Registration', 'ultimate-member' ),
                        'tooltip' => __( 'Select the UM Notification Email to send to the User when Admin is doing the User Registration.', 'ultimate-member' )
                    );

        $settings_structure['email']['fields'][] = array(
                        'id'      => 'um_wp_send_notification_to_admin',
                        'type'    => 'checkbox',
                        'label'   => __( 'WP Registration Notification Email to Admin', 'ultimate-member' ),
                        'tooltip' => __( 'Click the checkbox to deactivate the WP Registration Notification Email to Admin.', 'ultimate-member' )
                    );

        $settings_structure['email']['fields'][] = array(
                        'id'      => 'um_wp_send_notification_form_id',
                        'type'    => 'text',
                        'size'    => 'small',
                        'label'   => __( 'UM Administration Registration Form ID', 'ultimate-member' ),
                        'tooltip' => __( 'Design an UM Registration Form for updating the WP All Users "Info" popup and the email placeholder {submitted_registration} with the WP Form fields.', 'ultimate-member' )
                    );

        return $settings_structure;
    }

    public function um_email_notifications_admin_registration( $emails ) {

        $custom_emails = array(
                        'notification_admin_registration_email' => array(
                            'key'			 => 'notification_admin_registration_email',
                            'title'			 => __( 'Profile Created by Admin Email', 'ultimate-member' ),
                            'subject'		 => 'Profile {username} is created by Admin',
                            'body'			 => '',
                            'description'	 => __( 'To send a custom email to the user when profile is created by the site Administrator', 'ultimate-member' ),
                            'recipient'		 => 'user',
                            'default_active' => true
                        ));

        UM()->options()->options = array_merge( array(  'notification_admin_registration_email_on'  => 1,
                                                        'notification_admin_registration_email_sub' => 'Profile {username} is created by Admin', ), 
                                                UM()->options()->options );

        return array_merge( $custom_emails, $emails );
    }
}

new UM_Admin_Registration_Notification_Email();
