<?php

namespace Leat\Utils;

/**
 * Users Helper class.
 */
class Users
{
    /**
     * Create a new WooCommerce user from email address
     *
     * @param string $email
     * @param bool $send_notification Whether to send new user notification emails
     * @param array $user_data Additional user data such as first name, last name, etc.
     * @return \WP_User|\WP_Error
     */
    public static function create_woocommerce_user_from_email($email, $send_notification = false, $user_data = [])
    {
        $user = get_user_by('email', $email);

        if (! $user) {
            if (!$send_notification) {
                // Temporarily disable new user notification emails
                add_filter('wp_send_new_user_notification_to_user', '__return_false');
                add_filter('wp_send_new_user_notification_to_admin', '__return_false');
            }

            // Use username from user_data if provided, otherwise use email
            $username = !empty($user_data['username']) ? $user_data['username'] : $email;

            // Check if username already exists and generate a unique one if needed
            $base_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $base_username . $counter;
                $counter++;
            }

            // Remove username from user_data to prevent duplicate usage
            unset($user_data['username']);

            // Filter out null values
            $user_data = array_filter($user_data, function ($value) {
                return $value !== null;
            });

            // Prepare user data for insertion
            $new_user_data = array_merge([
                'user_login' => strtolower($username),
                'user_pass' => wp_generate_password(12, true, true),
                'user_email' => $email,
            ], $user_data);

            $user_id = wp_insert_user($new_user_data);

            if (is_wp_error($user_id)) {
                return $user_id; // Return the WP_Error object
            }

            if (!$send_notification) {
                // Remove our temporary filters
                remove_filter('wp_send_new_user_notification_to_user', '__return_false');
                remove_filter('wp_send_new_user_notification_to_admin', '__return_false');
            }

            $user = get_user_by('id', $user_id);

            // Check if get_user_by failed
            if (!$user) {
                return new \WP_Error('user_creation_failed', 'User was created but could not be retrieved.');
            }
        }

        return $user;
    }

    /**
     * Create a username from an email without the @ symbol.
     *
     * @param string $email
     * @return string
     */
    public static function create_username_from_email($email)
    {
        return str_replace('@', '', $email);
    }
}
