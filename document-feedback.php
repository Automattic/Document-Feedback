<?php
/*
Plugin Name: Document Feedback
Plugin URI: http://danielbachhuber.com/plugins/document-feedback/
Description: Close the loop &mdash; Get feedback from readers on the documentation you write
Version: 0.0
Author: Daniel Bachhuber
Author URI: http://danielbachhuber.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/*
Todo list:
- Show the avatar of the author next to the feedback prompt
- Ensure the styles work in most themes
- Build a post meta box that displays the data
*/

if ( !class_exists( 'Document_Feedback' ) ) {

class Document_Feedback {

	var $options;
	var $strings;
	var $post_types;

	/**
	 * Construct the plugin!
	 */
	function Document_Feedback() {

		add_action( 'init',                                        array( &$this, 'action_init_initialize_plugin' ) );
		add_action( 'admin_init',                                  array( &$this, 'action_admin_init_add_meta_box' ) );
		add_action( 'wp_enqueue_scripts',                          array( &$this, 'action_wp_enqueue_scripts_add_jquery' ) );
		add_action( 'wp_head',                                     array( &$this, 'ensure_ajaxurl' ), 1 );
		add_action( 'wp_ajax_document_feedback_form_submission',   array( &$this, 'action_wp_ajax_handle_form_submission' ) );
		add_filter( 'the_content',                                 array( &$this, 'filter_the_content_append_feedback_form' ) );
	}

	/**
	 * Initialize all of the plugin components
	 * Other plugins can register filters to modify how the plugin runs
	 *
	 * @since 0.1
	 */
	function action_init_initialize_plugin() {

		// Set up all of our plugin options but they can only be modified by filter
		$this->options = array(
				'send_notification'        => true, // Send an email to the author and contributors
				'throttle_limit'           => 3600, // How often (seconds) a user can submit a feedback
			);
		$this->options = apply_filters( 'document_feedback_options', $this->options );
		
		// Prepare the strings used in the plugin
		$this->strings = array(
				'prompt'          => __( "Did this document answer your question?", 'document-feedback' ),
				'accept'          => __( "Yes", 'document-feedback' ),
				'decline'         => __( "No", 'document-feedback' ),
				'prompt_response' => __( "Thanks for responding.", 'document-feedback' ),
				'accept_prompt'   => __( "What details were useful to you?", 'document-feedback' ),
				'decline_prompt'  => __( "What details are you still looking for?", 'document-feedback' ),
				'final_response'  => __( "Thanks for the feedback! We'll use it to improve our documentation.", 'document-feedback' ),
			);
		$this->strings = apply_filters( 'document_feedback_strings', $this->strings );

		// Establish the post types to request feedback on
		$this->post_types = array(
				'page',
			);
		$this->post_types = apply_filters( 'document_feedback_post_types', $this->post_types );
	}

	/**
	 * Hooks and such only to run in the admin
	 *
	 * @since 0.1
	 */
	function action_admin_init_add_meta_box() {

		foreach ( $this->post_types as $post_type ) {
			add_meta_box( 'document-feedback', __( 'Document Feedback', 'document-feedback'), array( &$this, 'post_meta_box'), $post_type, 'advanced', 'high');
		}
		
	}

	/**
	 * Add jQuery on relevant pages because we need it
	 *
	 * @since 0.1
	 */
	 function action_wp_enqueue_scripts_add_jquery() {
	 	global $post;
	 	if ( is_singular() && in_array( $post->post_type, $this->post_types ) && is_user_logged_in() )
			wp_enqueue_script( 'jquery' );
	 }

	 function ensure_ajaxurl() {
		if ( is_admin() || !is_user_logged_in() )
			return;
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
		//<![CDATA[
		var userSettings = {
				'url': '<?php echo SITECOOKIEPATH; ?>',
				'uid': '<?php echo $current_user->ID; ?>',
				'time':'<?php echo time() ?>'
			},
			ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		//]]>
		</script>
		<?php
	}

	/**
	 * Add a post meta box summarizing the feedback given on a document
	 *
	 * @since 0.1
	 *
	 * @todo Display the number of positive feedbacks vs. negative feedbacks
	 * @todo Display the most recent positive and negative feedbacks
	 */
	function post_meta_box() {
		?>
		<p>Nothing here yet.</p>
		<?php	
	}

	/**
	 * Handle a Document Feedback form submission
	 *
	 * @since 0.1
	 */
	function action_wp_ajax_handle_form_submission() {

		$error = false;

		// User must be logged in for all actions
		if ( !is_user_logged_in() )
			$error = new WP_Error( 'not-logged-in', __( 'You need to be logged in to submit feedback.', 'document-feedback' ) );

		// Nonce check
		if ( !wp_verify_nonce( $_POST['nonce'], 'document-feedback' ) )
			$error = new WP_Error( 'nonce-error', __( 'Nonce error. Are you sure you are who you say you are?', 'document-feedback' ) );

		// Feedback must be left on a valid post
		$post_id = (int)$_POST['post_id'];
		if ( false === ( $post = get_post( $post_id ) ) )
			$error = new WP_Error( 'invalid-post', __( 'Invalid post for feedback.', 'document-feedback' ) );
		else
			$post_id = $post->ID;

		// Check that the comment exists if we're passed a valid comment ID
		$comment_id = (int)$_POST['comment_id'];
		if ( $comment_id && ( false === ( $comment = get_comment( $comment_id ) ) ) )
			$error = new WP_Error( 'invalid-post', __( 'Invalid comment.', 'document-feedback' ) );
		else
			$comment_id = (int)$_POST['comment_id'];

		// @todo Ensure the user isn't hitting the throttle limit
			
		if ( is_wp_error( $error ) ) {
			$response = array(
					'status' => 'error',
					'message' => $error->get_error_message(),
				);
			echo json_encode( $response );
			exit;
		}

		$current_user = wp_get_current_user();

		// Form submission for the initial prompt
		// Create a new comment of accept or decline type against the current user
		if ( $_POST['form'] == 'prompt' ) {

			// Set up all of the base data for our comment
			$comment_data = array(
					'comment_post_id'       => $post_id,
					'comment_author'        => $current_user->display_name,
					'comment_author_email'  => $current_user->user_email,
					'comment_author_url'    => $current_user->user_url,
					'user_id'               => $current_user->ID,
				);
			
			// Set the comment type based on the value of the response
			if ( $_POST['response'] == 'accept' )
				$comment_data['comment_type'] = 'df-accept';
			if ( $_POST['response'] == 'decline' )
				$comment_data['comment_type'] = 'df-decline';

			$comment_id = wp_insert_comment( $comment_data );
			$response = array(
					'status' => 'success',
					'message' => 'comment-id-' . $comment_id,
				);
		}
		// Follow up response form submission
		// Save the message submitted as the message in the comment
		else {
			$response = array(
					'status' => 'success',
					'message' => $comment_id,
				);
		}

		echo json_encode( $response );
		exit;
	}

	/**
	 * Append the document feedback form to the document
	 * We're using ob_*() functions to maintain readability of the form
	 *
	 * @since 0.1
	 */
	function filter_the_content_append_feedback_form( $the_content ) {
		global $post;

		if ( !is_singular() || !in_array( $post->post_type, $this->post_types ) || !is_user_logged_in() )
			return $the_content;

		// @todo Show a message if the user submitted a response in the last X minutes

		// Javascript for the form
		ob_start(); ?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('#document-feedback .document-feedback-form input.button').click(function(){
					var button_id = jQuery(this).attr('id');
					var comment_id = jQuery('#document-feedback-comment-id').val();
					var post_id = jQuery('#document-feedback-post-id').val();
					var nonce = jQuery('#document-feedback-nonce').val();
						
					if ( button_id == 'document-feedback-accept-button' ) {
						var form = 'prompt';
						var response = 'accept';
					} else if ( button_id == 'document-feedback-decline-button' ) {
						var form = 'prompt';
						var response = 'decline';
					} else {
						var form = 'response';
						var response = jQuery(this).sibling('.document-feedback-response').val();
					}
					var df_data = {
						action: 'document_feedback_form_submission',
						form: form,
						nonce: nonce,
						response: response,
						post_id: post_id,
						comment_id: comment_id,
					};
					jQuery.post( ajaxurl, df_data, function( response ) {
						console.log( response );
						return false;
					});
					return false;
				});
			});
		</script>
		<?php
		$script = ob_get_contents();
		ob_end_clean();

		// Styles for the form
		ob_start(); ?>
		<style type="text/css">
			#document-feedback {
				border-top: 1px solid #EEE;
				margin-top: 10px;
				padding-top: 10px;
			}
			#document-feedback #document-feedback-accept,
			#document-feedback #document-feedback-decline {
				display: none;
			}
			#document-feedback label.block {
				display:block;
			}
			#document-feedback input.medium {
				width: 70%;
			}
			#document-feedback #document-feedback-prompt label {
				margin-right: 20px;
			}
		</style>
		<?php
		$styles = ob_get_contents();
		ob_end_clean();

		// Initial prompt
		ob_start(); ?>
		<form id="document-feedback-prompt" class="document-feedback-form" method="POST" action="">
			<label><?php echo esc_html( $this->strings['prompt'] ); ?></label>
			<input type="submit" class="button" id="document-feedback-accept-button" name="document-feedback-accept-button" value="<?php echo esc_attr( $this->strings['accept'] ); ?>" />
			<input type="submit" class="button" id="document-feedback-decline-button" name="document-feedback-decline-button" value="<?php echo esc_attr( $this->strings['decline'] ); ?>" />
		</form>
		<?php
		$prompt = ob_get_contents();
		ob_end_clean();

		// Follow-up accept question
		ob_start(); ?>
		<form id="document-feedback-accept" class="document-feedback-form" method="POST" action="">
			<label class="block" for="document-feedback-accept-response"><?php echo esc_html( $this->strings['prompt_response'] . ' ' . $this->strings['accept_prompt'] ); ?></label>
			<input type="text" class="medium" id="document-feedback-accept-response" name="document-feedback-accept-response" class="document-feedback-response" />
			<input type="submit" class="button document-feedback-submit-response" name="submit" value="<?php _e( 'Send feedback', 'document-feedback' ); ?>" />
		</form>
		<?php
		$accept = ob_get_contents();
		ob_end_clean();

		// Follow-up decline question
		ob_start(); ?>
		<form id="document-feedback-decline" class="document-feedback-form" method="POST" action="">
			<label class="block" for="document-feedback-decline-response"><?php echo esc_html( $this->strings['prompt_response'] . ' ' . $this->strings['decline_prompt'] ); ?></label>
			<input type="text" class="medium" id="document-feedback-decline-response" name="document-feedback-decline-response" class="document-feedback-response" />
			<input type="submit" class="button document-feedback-submit-response" name="submit" value="<?php _e( 'Send feedback', 'document-feedback' ); ?>" />
		</form>
		<?php
		$decline = ob_get_contents();
		ob_end_clean();

		// Other data to store in a hidden fashion
		ob_start(); ?>
		<input type="hidden" id="document-feedback-post-id" value="<?php the_id(); ?>" />
		<input type="hidden" id="document-feedback-comment-id" value="0" />
		<?php wp_nonce_field( 'document-feedback', 'document-feedback-nonce' ); ?>
		<?php
		$data = ob_get_contents();
		ob_end_clean();

		return $the_content . $script . $styles . '<div id="document-feedback">' . $prompt . $accept . $decline . $data . '</div>';
		
	}

}

}

global $document_feedback;
$document_feedback = new Document_Feedback();