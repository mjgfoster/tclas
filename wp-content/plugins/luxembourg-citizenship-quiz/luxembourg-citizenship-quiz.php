<?php
/**
 * Plugin Name: Luxembourg Citizenship Quiz
 * Description: A dynamic, generation-by-generation shortcode quiz to determine eligibility for Luxembourgish citizenship.
 * Version: 2.1
 * Author: Matthew J. Foster
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

const LCQ_RATE_MAX = 5;   // result emails per IP per hour

// ═══════════════════════════════════════════════════════════════════════════
// Outcomes — the single source of truth
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Every outcome the quiz can reach, keyed by the identifier the front end uses.
 *
 * This copy lives here rather than in lcq-quiz.js so that the screen and the
 * emailed copy can never drift apart, and — more importantly — so the email
 * endpoint never has to trust text supplied by the browser. The client sends a
 * key; the server sends its own words.
 *
 * `type` is carried for the planned PDF packet (see PLAN-PDF-PACKET.md); the
 * quiz itself does not branch on it.
 */
function lcq_outcomes(): array {
	return [
		// ── Step 1 gate outcomes ─────────────────────────────────────────
		'outcome_ineligible_no_ancestor' => [
			'headline' => 'Luxembourg citizenship by descent requires a Luxembourgish ancestor.',
			'body'     => 'This quiz helps determine eligibility for citizenship by descent — which requires having at least one ancestor who was a citizen of the Grand Duchy of Luxembourg. Without a known Luxembourgish ancestor, the Article 7 and Article 23 descent pathways are not available. If you believe you may have Luxembourgish roots but aren’t certain, genealogical research or a consultation with the Luxembourg National Archives (ANLux) may be a good starting point.',
			'type'     => null,
		],
		'outcome_ineligible_territory' => [
			'headline' => 'Your ancestor may have been born in Belgium, not Luxembourg.',
			'body'     => 'The Province of Luxembourg — now part of southeastern Belgium — was historically part of the same region as the Grand Duchy until 1839, when the Treaty of London divided the territory. Modern Luxembourg citizenship law applies only to citizens of the Grand Duchy. If your ancestor was born in a town now in Belgium (such as Arlon, Bastogne, or Libramont), the Article 7 descent pathway does not apply to that line. We encourage you to double-check your ancestor’s birthplace against a current map of the Grand Duchy of Luxembourg, then contact the Luxembourg Ministry of Justice if you have questions about your specific situation.',
			'type'     => null,
		],
		'outcome_unsure_ancestor' => [
			'headline' => 'You’ll want to confirm your Luxembourg connection first.',
			'body'     => 'Before taking this quiz, it helps to know whether you have at least one ancestor who was a citizen of the Grand Duchy of Luxembourg. If you’re not sure, genealogical research is a great starting point. The Luxembourg National Archives (ANLux), parish registers, and online databases can help you trace your family history. Once you’ve confirmed a Luxembourgish ancestor, come back and retake this quiz.',
			'type'     => null,
		],
		'outcome_unsure_territory' => [
			'headline' => 'Worth confirming before you go further.',
			'body'     => 'The historical Province of Luxembourg and the modern Grand Duchy share a name and much of their history — but they are different countries today. The 1839 Treaty of London split the original duchy: the western, French-speaking portion became a Belgian province, while the eastern portion remained the independent Grand Duchy. If your ancestor’s records show “Luxembourg” as a birthplace, cross-reference the specific commune against a current map of the Grand Duchy. The Luxembourg National Archives (ANLux) and genealogical databases such as Portail Généalogique Grand-Ducal can help confirm whether a town is inside modern Luxembourg. Once verified, you can retake this quiz with that detail confirmed.',
			'type'     => null,
		],

		// ── Lineage outcomes ─────────────────────────────────────────────
		'outcome_adopted' => [
			'headline' => 'Your situation has some unique nuances.',
			'body'     => 'If you were adopted into a Luxembourgish family, you may qualify for citizenship—but you’ll have to contact someone to discuss your case.',
			'type'     => null,
		],
		'outcome_too_deep' => [
			'headline' => 'Your Luxembourgish connection appears to go back many generations.',
			'body'     => 'This quiz traces ancestry up to seven generations. Your Luxembourg connection appears to be beyond that range, which is outside the scope of the standard Article 7 pathway and beyond the reach of Article 23. While this makes qualifying by descent unlikely under current law, every family’s records are different, and there may be details in your specific lineage that change the picture. We encourage you to consult directly with the Luxembourg Ministry of Justice or a citizenship specialist.',
			'type'     => null,
		],
		'outcome_article7' => [
			'headline' => 'It looks like you may qualify through Article 7 (Direct Descent).',
			'body'     => 'Based on your answers, your Luxembourgish bloodline appears to have passed unbroken from generation to generation. Under Article 7 of the Luxembourg Nationality Act, you likely already hold citizenship by birthright—you simply need to formally claim and register it. The process is handled entirely by mail; there is no language test, no travel to Luxembourg, and no residency requirement. Your qualifying ancestor must have been born between 1815 and 1946 within the borders of modern-day Luxembourg (not the former Belgian Luxembourg province).',
			'type'     => 'article7',
		],
		'outcome_article23_living' => [
			'headline' => 'It looks like you may qualify through Article 23.',
			'body'     => 'Because a female ancestor in your line passed citizenship to a child born before 1969, the direct Article 7 line was technically broken under the law of that era. Article 23 exists specifically to address this situation. However, it’s a two-step process: your living parent or grandparent must first be formally recognized as a Luxembourg citizen through their own Article 7 application. Once they receive recognition, you can then apply for nationality through Article 23. Note: Article 23 extends only one generation—the connecting relative must be your parent or grandparent, not a great-grandparent. The Article 23 process requires an in-person appointment at the Luxembourg Ministry of Justice in Luxembourg City, with roughly a four-month waiting period.',
			'type'     => 'article23',
		],
		'outcome_article23_deceased' => [
			'headline' => 'It looks like you may qualify through the Article 7 + Article 23 (Posthumous) pathway.',
			'body'     => 'Because a female ancestor in your line passed citizenship to a child born before 1969, the direct Article 7 line was technically broken. However, a two-phase process may still be available. In Phase 1, you petition for posthumous recognition of your late parent or grandparent as someone who would have qualified for Luxembourg nationality under Article 7. If granted, their citizenship is recognized retroactively. In Phase 2, you then apply for nationality yourself under Article 23. This pathway is more involved, but it has been successfully completed by other Americans navigating the same situation. An in-person appointment in Luxembourg City will be required.',
			'type'     => 'article23',
		],
	];
}

/**
 * Relationship label for a generation. Mirrors genLabel() in lcq-quiz.js so the
 * emailed family tree reads the same as the one on screen.
 */
function lcq_gen_label( int $index, string $gender ): string {
	if ( $index <= 0 ) {
		return 'f' === $gender ? 'mom' : 'dad';
	}
	$base = 'f' === $gender ? 'grandmother' : 'grandfather';
	if ( 1 === $index ) {
		return $base;
	}
	return str_repeat( 'great-', $index - 1 ) . $base;
}

/**
 * Render the family-tree summary from the structured lineage the client sends.
 *
 * The browser sends only gender/flags per generation — never prose. Labels are
 * derived here, so the worst a tampered payload can do is describe a family
 * tree that the sender already made up for themselves.
 */
function lcq_lineage_text( array $lineage, ?bool $user_before_1969 ): string {
	if ( ! $lineage ) {
		return '';
	}

	$lines = [ 'Your lineage:' ];
	if ( null !== $user_before_1969 ) {
		$lines[] = '- You (' . ( $user_before_1969 ? 'born before 1969' : 'born after 1969' ) . ')';
	}

	foreach ( array_values( $lineage ) as $i => $person ) {
		$gender = ( isset( $person['g'] ) && 'f' === $person['g'] ) ? 'f' : 'm';
		$parts  = [ lcq_gen_label( $i, $gender ) ];

		if ( isset( $person['b69'] ) && null !== $person['b69'] ) {
			$parts[] = $person['b69'] ? 'born before 1969' : 'born after 1969';
		}
		if ( ! empty( $person['lux'] ) ) {
			$parts[] = 'born in Luxembourg';
		}

		$lines[] = '- ' . implode( ', ', $parts );
	}

	return implode( "\n", $lines );
}

/**
 * Normalise the lineage payload into a small, strictly-typed structure.
 * Anything unexpected is dropped rather than corrected.
 */
function lcq_sanitize_lineage( $raw ): array {
	if ( is_string( $raw ) ) {
		$raw = json_decode( wp_unslash( $raw ), true );
	}
	if ( ! is_array( $raw ) ) {
		return [];
	}

	$clean = [];
	foreach ( array_slice( array_values( $raw ), 0, 7 ) as $person ) {
		if ( ! is_array( $person ) ) {
			continue;
		}
		$clean[] = [
			'g'   => ( isset( $person['g'] ) && 'f' === $person['g'] ) ? 'f' : 'm',
			'b69' => isset( $person['b69'] ) && null !== $person['b69'] ? (bool) $person['b69'] : null,
			'lux' => ! empty( $person['lux'] ),
		];
	}

	return $clean;
}

// ═══════════════════════════════════════════════════════════════════════════
// Assets
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', 'lcq_enqueue_quiz_scripts' );
function lcq_enqueue_quiz_scripts() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'luxembourg_eligibility_quiz' ) ) {
        wp_enqueue_style( 'lcq-quiz-styles', plugin_dir_url( __FILE__ ) . 'lcq-styles.css', array(), '2.1' );
        wp_enqueue_script( 'lcq-quiz-script', plugin_dir_url( __FILE__ ) . 'lcq-quiz.js', array(), '2.1', true );

        // Outcome copy travels to the browser from here, so the screen renders
        // exactly the words the email will contain.
        $outcomes = array();
        foreach ( lcq_outcomes() as $key => $o ) {
            $outcomes[ $key ] = array(
                'headline' => $o['headline'],
                'body'     => $o['body'],
                'type'     => $o['type'],
            );
        }

        wp_localize_script( 'lcq-quiz-script', 'lcqData', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'lcq_email_nonce' ),
            'outcomes'    => $outcomes,
            'offer_optin' => function_exists( 'tclas_signup_begin_double_optin' ),
        ) );
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Shortcode
// ═══════════════════════════════════════════════════════════════════════════

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

// ═══════════════════════════════════════════════════════════════════════════
// AJAX — email the results
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_lcq_send_results', 'lcq_handle_email_submission' );
add_action( 'wp_ajax_nopriv_lcq_send_results', 'lcq_handle_email_submission' );

/**
 * Email a visitor their own quiz results.
 *
 * This endpoint is unauthenticated by design — the quiz is public — so it is
 * built to be dull to abuse: the body is assembled from server-side copy keyed
 * by a validated outcome identifier, never from text the browser supplies, and
 * submissions are throttled per IP. Joining the email list is a separate,
 * explicit choice that goes through the site's double opt-in, so an address
 * only reaches Brevo after its owner clicks a confirmation link.
 */
function lcq_handle_email_submission() {
    check_ajax_referer( 'lcq_email_nonce', 'nonce' );

    // Throttle before doing any work. Shares the theme's per-IP limiter when
    // it's available, with its own bucket so the quiz and /email-list/ don't
    // spend each other's allowance.
    if ( function_exists( 'tclas_signup_rate_ok' ) && ! tclas_signup_rate_ok( 'quiz', LCQ_RATE_MAX ) ) {
        wp_send_json_error( 'Too many requests. Please wait a little while before trying again.' );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Invalid email address.' );
    }

    // The client picks which outcome, not what it says.
    $outcomes = lcq_outcomes();
    $key      = isset( $_POST['outcome'] ) ? sanitize_key( wp_unslash( $_POST['outcome'] ) ) : '';
    if ( ! isset( $outcomes[ $key ] ) ) {
        wp_send_json_error( 'Unknown result.' );
    }
    $outcome = $outcomes[ $key ];

    $lineage = lcq_sanitize_lineage( $_POST['lineage'] ?? '' );

    $user_before_1969 = null;
    if ( isset( $_POST['user_before_1969'] ) && '' !== $_POST['user_before_1969'] ) {
        $user_before_1969 = filter_var( wp_unslash( $_POST['user_before_1969'] ), FILTER_VALIDATE_BOOLEAN );
    }

    $lineage_text = lcq_lineage_text( $lineage, $user_before_1969 );

    $body  = $outcome['headline'] . "\n\n";
    if ( $lineage_text ) {
        $body .= $lineage_text . "\n\n";
    }
    $body .= $outcome['body'] . "\n\n";
    $body .= "— — —\n";
    $body .= "This quiz is strictly informational and does not constitute legal advice. It is not written or reviewed by an attorney. Luxembourg citizenship law is complex and individual circumstances vary. For a conclusive determination, please contact the Luxembourg Ministry of Justice: https://mj.gouvernement.lu/en/particuliers/nationalite.html\n";

    $sent = wp_mail(
        $email,
        'Your Luxembourg Citizenship Eligibility Results',
        $body,
        array( 'Content-Type: text/plain; charset=UTF-8' )
    );

    if ( ! $sent ) {
        wp_send_json_error( 'There was a problem sending the email.' );
    }

    // Newsletter opt-in is opt-IN: absent or unchecked means no subscription,
    // and even when checked nothing is written to Brevo until the confirmation
    // link is clicked.
    $wants_list = ! empty( $_POST['subscribe'] ) && filter_var( wp_unslash( $_POST['subscribe'] ), FILTER_VALIDATE_BOOLEAN );
    $confirming = false;

    if ( $wants_list && function_exists( 'tclas_signup_begin_double_optin' ) ) {
        // lcq_brevo_list_id predates the Brevo migration and is usually unset.
        // When it is, quiz opt-ins join the same list as /email-list/ rather
        // than becoming list-less contacts, which is what the old direct-subscribe
        // call produced. The checkbox promises "TCLAS news" — this is that list.
        $list_id = (int) get_option( 'lcq_brevo_list_id', 0 );
        if ( $list_id <= 0 && function_exists( 'tclas_signup_list_id' ) ) {
            $list_id = tclas_signup_list_id();
        }

        $already = function_exists( 'tclas_signup_already_subscribed' )
            && tclas_signup_already_subscribed( $email, $list_id );

        if ( ! $already ) {
            $confirming = tclas_signup_begin_double_optin( array(
                'email' => $email,
                'lists' => $list_id ? array( $list_id ) : array(),
                'tag'   => 'quiz-completer',
            ) );
        }
    }

    wp_send_json_success( array(
        'message'    => 'Results sent! Check your inbox.',
        'confirming' => $confirming,
    ) );
}
