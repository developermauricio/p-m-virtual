<?php
/*
Plugin Name: CL CustomLogin
Description: A plugin custom login
Version: 0.1
Author: CL
Text Domain: custom login
License: GPLv2
*/

require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;


add_action('wp', 'cl_check_login');
add_action('wp_head', 'cl_display_user');

$current_user = null;

function get_user_data($token, $key)
{
    if (!$token || !$key) {
        return null;
    }

    try {
        $dataToken = (array) JWT::decode($token, $key, ['HS256']);
        //Array ( [iat] => 1633722015 [exp] => 1633725615 [data] => stdClass Object ( [userId] => 10904 [user] => Omar David [email] => odsanchez34@gmail.com [Ciudad] => Popayán [Edad] => 23 ) )
        return $dataToken['data'];
    } catch (\Throwable $th) {
        print_r($th->getMessage());
    }
    return null;
}
// global $wp;

function configure_user($user_id, $name)
{
    $userdata = [
        'ID' => $user_id,
        'first_name' => $name,
        'display_name' => $name,
        'admin_bar_front' => 0
    ];

    wp_update_user($userdata);
    update_user_option($user_id, 'show_admin_bar_front', 'false');
}

function add_permissions($user_id)
{
    $custom_level = array(
        'user_id' => $user_id,
        'membership_id' => 1,
        'code_id' => null,
        'initial_payment' => null,
        'billing_amount' => null,
        'cycle_number' => null,
        'cycle_period' => null,
        'billing_limit' => null,
        'trial_amount' => null,
        'trial_limit' => null,
        'status' => null,
        'startdate' => null,
        'enddate' => null
    );

    pmpro_changeMembershipLevel($custom_level, $user_id);
}


function new_user($user, $password)
{
    $user_email = $user->email;
    $emailParts = explode('@', $user->email);
    $user_name = $emailParts[0] . $emailParts[1][0] . $emailParts[1][1];

    $user_id = username_exists($user_name);

    if (!$user_id && !email_exists($user_email)) {
        $user_id = wp_create_user(
            $user_name,
            $password,
            $user_email
        );

        configure_user($user_id, $user->user);
        add_permissions($user_id);
    } else {
        $user_id = email_exists($user_email);
    }

    return $user_id;
}

function login_user($user, $password)
{
    $user_id = new_user($user, $password);

    if ($user_id) {
        $credentials = [
            'user_login' => $user->email,
            'user_password' => $password,
            'rememberme' => true
        ];
        $user = wp_signon($credentials);
        if (is_wp_error($user)) {
            echo ("<pre>");
            print_r($user);
            die('</pre>');
        } else {
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

function add_var($name, $value)
{
    if (is_numeric($value)) {
        return "const {$name}={$value};";
    }
    return "const {$name}=\"{$value}\";";
}

function cl_check_login()
{
    //set vars
    $key = '4pp3v3ntm0v1lC0m63*f2ec0h421';
    $password = $key . "p&d2021";

    $token = $_REQUEST['token'];
    if ($token) {
        $user = get_user_data($token, $key);
    }

    if (!$user) {
        return;
    }

    $current_user = wp_get_current_user();
    if (!($current_user instanceof WP_User)) {
        return;
    }

    if ($current_user && $current_user->user_email) {
        if ($current_user->user_email !== $user->email) {
            wp_logout();
            login_user($user, $password);
        }
    } else {
        login_user($user, $password);
    }
}


function cl_display_user()
{
    $current_user = wp_get_current_user();
    if (!($current_user instanceof WP_User)) {
        return;
    }

    $vars = add_var('user_email', $current_user->user_email)
        . add_var('user_first_name', $current_user->user_firstname)
        . add_var('user_last_name', $current_user->user_lastname)
        . add_var('user_display_name', $current_user->display_name)
        . add_var('user_id', $current_user->ID)
        . add_var('user_user_name', $current_user->user_login);
    echo ("<script>$vars</script>");
}
