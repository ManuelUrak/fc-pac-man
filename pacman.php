<?php 

/*
Plugin Name: Fat Cat Pac-Man Game
Description: A pacman game for WordPress.
Version: 1.0
Author: Manuel Urak, manuel.urak88@gmail.com
*/

// Avoid direct access to this file

if(!defined('ABSPATH')){
    exit;
}

//Create the highscores table on plugin activation

function fc_pac_man_create_highscores_table(){
    global $wpdb;
    $table_name = $wpdb -> prefix . 'pacman_high_scores';
    $charset_collate = $wpdb -> get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        player_name varchar(255) NOT NULL,
        score int NOT NULL,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'fc_pac_man_create_highscores_table');

// Enqueue scripts and styles

function fc_pac_man_enqueue_scripts(){
    //Enqueue the game main script
    wp_enqueue_script(
        'fc_pac_man',
        plugins_url('assets/js/game.js', __FILE__),
        array(),
        '1.0',
        true 
    );

    //Enqueue styles
    wp_enqueue_style(
        'fc_pac_man_style',
        plugins_url('style.css', __FILE__),
        array(),
        '1.0'
    );

    //Localize script for asset paths
    wp_localize_script(
        'fc_pac_man',
        'fcPacMan',
        array(
            'imgs' => plugins_url('assets/imgs/', __FILE__),
            'sounds' => plugins_url('assets/sounds/', __FILE__),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fc_pac_man_nonce') 
        )
    );
}
add_action('wp_enqueue_scripts', 'fc_pac_man_enqueue_scripts');

// Apply module type to the script

function fc_pac_man_add_module_attribute($tag, $handle) {
    if ($handle === 'fc_pac_man') {
        return str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'fc_pac_man_add_module_attribute', 10, 2);

//Create a shortcode to display the game

function fc_pac_man_shortcode(){
    global $shortcode_rendered;
    $shortcode_rendered = true;

    return '
        <div id="game-container">
            <h1>Pac Man</h1>
            <canvas id="gameCanvas"></canvas>
            <div class="information">
                <span id="score"></span>
                <span id="level"></span>
            </div>
            <div id="high-scores"></div>
            <div id="custom-prompt" class="modal">
                <div class="modal-content">
                    <h2>Enter Your Name</h2>
                    <input type="text" id="player-name" placeholder="Your name" />
                    <button id="submit-name">Submit</button>
                </div>
            </div>
        </div>
    ';
}
add_shortcode('pacman_game', 'fc_pac_man_shortcode');

// Check if the shortcode is present in the post content

function fc_pac_man_check_for_shortcode() {
    global $post;
    global $shortcode_rendered;

    // Check if the current post contains the shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pacman_game')) {
        $shortcode_rendered = true;
    }
}
add_action('template_redirect', 'fc_pac_man_check_for_shortcode');

// Add a class to the body tag if the shortcode is rendered

function fc_pac_man_add_body_class($classes) {
    global $shortcode_rendered;

    if (isset($shortcode_rendered) && $shortcode_rendered) {
        $classes[] = 'pac-man';
    }

    return $classes;
}
add_filter('body_class', 'fc_pac_man_add_body_class');

//Save highscore to the database

function fc_pac_man_save_highscore($player_name, $score){
    global $wpdb;
    $table_name = $table_name = $wpdb -> prefix . 'pacman_high_scores';

    $wpdb -> insert(
        $table_name,
        array(
            'player_name' => sanitize_text_field($player_name),
            'score' => intval($score)
        )
    );
}

//Retrieve highscores from the database

function fc_pac_man_get_highscores(){
    global $wpdb;
    $table_name = $table_name = $wpdb -> prefix . 'pacman_high_scores';

    $results = $wpdb -> get_results(
        "SELECT player_name, score FROM $table_name ORDER BY score DESC LIMIT 10"
    );

    return $results;
}

//Handle AJAX request to save a highscore

function fc_pac_man_ajax_save_highscore(){
    check_ajax_referer('fc_pac_man_nonce', '_ajax_nonce');

    if(isset($_POST['player_name']) && isset($_POST['score'])){
        $player_name = sanitize_text_field($_POST['player_name']);
        $score = intval($_POST['score']);

        fc_pac_man_save_highscore($player_name, $score);
        wp_send_json_success('High score saved');
    } else{
        wp_send_json_error('Invalid data');
    }
}
add_action('wp_ajax_fc_pac_man_save_highscore', 'fc_pac_man_ajax_save_highscore');
add_action('wp_ajax_nopriv_fc_pac_man_save_highscore', 'fc_pac_man_ajax_save_highscore');

//Handle AJAX request to retrieve highscores

function fc_pac_man_ajax_get_highscores(){
    check_ajax_referer('fc_pac_man_nonce', '_ajax_nonce');

    $high_scores = fc_pac_man_get_highscores();
    wp_send_json_success($high_scores);
}
add_action('wp_ajax_fc_pac_man_get_highscores', 'fc_pac_man_ajax_get_highscores');
add_action('wp_ajax_nopriv_fc_pac_man_get_highscores', 'fc_pac_man_ajax_get_highscores');