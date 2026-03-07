<?php
/**
 * Plugin Name: Luxembourg Citizenship Quiz
 * Description: A dynamic, generation-by-generation shortcode quiz to determine eligibility for Luxembourgish citizenship.
 * Version: 2.0
 * Author: Matthew J. Foster
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Enqueue Scripts and Styles
add_action( 'wp_enqueue_scripts', 'lcq_enqueue_quiz_scripts' );
function lcq_enqueue_quiz_scripts() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'luxembourg_eligibility_quiz' ) ) {
        wp_enqueue_style( 'lcq-quiz-styles', plugin_dir_url( __FILE__ ) . 'lcq-styles.css', array(), '2.0' );
        wp_enqueue_script( 'lcq-quiz-script', plugin_dir_url( __FILE__ ) . 'lcq-quiz.js', array(), '2.0', true );
        
        wp_localize_script( 'lcq-quiz-script', 'lcqData', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'lcq_email_nonce' )
        ) );
    }
}

// The Shortcode Output
add_shortcode( 'luxembourg_eligibility_quiz', 'lcq_eligibility_quiz_shortcode' );
function lcq_eligibility_quiz_shortcode() {
    ob_start(); ?>
    
    <div id="lcq-quiz-layout" class="cq_layout">

        <div id="lcq-quiz-container" class="cq_quiz_container" aria-live="polite" aria-atomic="true">

            <div class="cq_progress_wrapper">
                <div id="lcq-progress-bar"
                     class="cq_progress_bar"
                     role="progressbar"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-valuenow="0"
                     style="width: 0%; transition: width 0.3s ease;">
                </div>
            </div>

            <h3 id="lcq-question-text" class="cq_question_text">Loading quiz...</h3>
            <div id="lcq-button-container" class="cq_button_container"></div>
        </div>

        <aside id="lcq-lineage-sidebar" class="cq_sidebar" aria-label="Your family tree" hidden>
            <h4 class="cq_sidebar__title">Your family tree</h4>
            <ol id="lcq-lineage-list" class="cq_lineage_list"></ol>
        </aside>

    </div>

    <?php
    return ob_get_clean();
}

// AJAX Handler for Emailing Results + Brevo Subscribe
add_action( 'wp_ajax_lcq_send_results', 'lcq_handle_email_submission' );
add_action( 'wp_ajax_nopriv_lcq_send_results', 'lcq_handle_email_submission' );
function lcq_handle_email_submission() {
    check_ajax_referer( 'lcq_email_nonce', 'nonce' );

    $email = sanitize_email( $_POST['email'] );
    $result_text = sanitize_text_field( $_POST['result_text'] );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Invalid email address.' );
    }

    // Subscribe to Brevo (General Subscribers list) with quiz-completer tag
    if ( function_exists( 'tclas_brevo_subscribe' ) ) {
        $general_list_id = (int) get_option( 'lcq_brevo_list_id', 0 );
        $list_ids = $general_list_id ? [ $general_list_id ] : [];
        tclas_brevo_subscribe( $email, [], $list_ids, 'quiz-completer' );
    }

    $subject = 'Your Luxembourg Citizenship Eligibility Results';
    $message = "Here are your results from the eligibility quiz:\n\n" . $result_text . "\n\nNote: This is an automated assessment and does not constitute legal advice.";
    $headers = array('Content-Type: text/plain; charset=UTF-8');

    if ( wp_mail( $email, $subject, $message, $headers ) ) {
        wp_send_json_success( 'Results sent successfully!' );
    } else {
        wp_send_json_error( 'There was a problem sending the email.' );
    }
}