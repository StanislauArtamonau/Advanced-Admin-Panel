<?php
function adv_adm_pan_scripts()
{
    $theme_version = wp_get_theme()->get('Version');
    wp_enqueue_style('adv-adm-pan-style', get_template_directory_uri() . '/assets/css/main.css', [], $theme_version);
    wp_enqueue_script('adv-adm-pan-script', get_template_directory_uri() . '/assets/js/main.js', [], $theme_version, true);
}
add_action('wp_enqueue_scripts', 'adv_adm_pan_scripts');