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
            'nonce' => wp_create_nonce('fc_pac_man_nonce'),
            'sound_enabled' => get_option('fc_pac_man_sound_enabled', true)
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

//Register the shortcode with the configured slug

function fc_pac_man_register_shortcode(){
    $shortcode_slug = get_option('fc_pac_man_shortcode_slug', 'pacman_game');
    add_shortcode($shortcode_slug, 'fc_pac_man_shortcode');
}
add_action('init', 'fc_pac_man_register_shortcode');

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

// Plugin Icon

$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
  <path d="M12 2a10 10 0 1 0 10 10h-9.5L20 6.5A10 10 0 0 0 12 2zM16.5 12a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0z" />
</svg>';
$icon_encoded = 'data:image/svg+xml;base64,' . base64_encode($icon);

// Add a settings page to the wordpress dashboard

function fc_pac_man_add_settings_page(){
    global $icon_encoded;

    add_menu_page(
        'Pac-Man Settings',
        'Pac-Man',
        'manage_options',
        'fc-pac-man-settings',
        'fc_pac_man_render_settings_page',
        $icon_encoded,
        100
    );
}
add_action('admin_menu', 'fc_pac_man_add_settings_page');

// Render the settings page

function fc_pac_man_render_settings_page(){
    ?>

    <div class="wrap">
        <h1>Pac-Man Settings</h1>
        <form method="post" action="options.php">
            <?php 
            
            settings_fields('fc_pac_man_settings_group');
            do_settings_sections('fc-pac-man-settings');
            submit_button();

            ?>
        </form>
    </div>

    <?php
}

// Register settings and fields

function fc_pac_man_register_settings(){
    //Register setting for the sound options
    register_setting(
        'fc_pac_man_settings_group',
        'fc_pac_man_sound_enabled',
        array(
            'type' => 'boolean',
            'sanitize_callback' => 'fc_pac_man_sanitize_checkbox',
            'default' => true
        )
    );

    // Register a setting for the shortcode slug
    register_setting(
        'fc_pac_man_settings_group', 
        'fc_pac_man_shortcode_slug', 
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'pacman_game'
        )
    );

    // Add a section for the settings
    add_settings_section(
        'fc_pac_man_main_section', 
        'Game Settings',           
        'fc_pac_man_section_text', 
        'fc-pac-man-settings'      
    );

    // Add a field for the sound option
    add_settings_field(
        'fc_pac_man_sound_enabled', 
        'Enable Sound',             
        'fc_pac_man_sound_checkbox', 
        'fc-pac-man-settings',      
        'fc_pac_man_main_section'   
    );

    // Add a field for the shortcode slug
    add_settings_field(
        'fc_pac_man_shortcode_slug', 
        'Shortcode Slug',            
        'fc_pac_man_shortcode_input', 
        'fc-pac-man-settings',       
        'fc_pac_man_main_section'    
    );
}
add_action('admin_init', 'fc_pac_man_register_settings');

// Settings description

function fc_pac_man_section_text(){
    echo '<p>Configure the Pac-Man Game</p>';
}

// Render the checkbox for the sound setting

function fc_pac_man_sound_checkbox(){
    $sound_enabled = get_option('fc_pac_man_sound_enabled', true);

    ?>

    <input type="checkbox" name="fc_pac_man_sound_enabled" value="1" <?php checked(1, $sound_enabled) ?>>
    <label for="fc_pac_man_sound_enabled">Enable or disable sound</label>

    <?php
}

// Render the shortcode input field

function fc_pac_man_shortcode_input(){
    $shortcode_slug = get_option('fc_pac_man_shortcode_slug', 'pacman_game');

    ?>

    <input type="text" name="fc_pac_man_shortcode_slug" value="<?php echo esc_attr($shortcode_slug); ?>">
    <p class="description">The shortcode to display the game on your page or post. Default: <code>[pacman_game]</code></p>

    <?php
}

// Sanitize checkbox input

function fc_pac_man_sanitize_checkbox($input){
    return (bool) $input;
}