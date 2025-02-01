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
            'sounds' => plugins_url('assets/sounds/', __FILE__)
        )
    );
}
add_action('wp_enqueue_scripts', 'fc_pac_man_enqueue_scripts');

//Create a shortcode to display the game

function fc_pac_man_shortcode(){
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

