<?php
	/**
	 * Plugin Name: Custom CV Form by TechnoPreacher
	 * Description: CV form with file upload & shortcode [ccvftp]
	 * Version: 1.0
	 * Text Domain: ccvftp_domain
	 * Domain Path: /lang/
	 * Author: TechnoPreacher
	 * License: GPLv2 or later
	 * Requires at least: 5.0
	 * Requires PHP: 7.4
	 */

	register_activation_hook( __FILE__, 'ccvftp_activation_hook_action' );
	register_deactivation_hook( __FILE__, 'ccvftp_deactivation_hook_action' );// remove plugin actions.

	add_action( 'wp', 'ccvftp_wp_action' );
	add_action( 'plugins_loaded', 'ccvftp_plugins_loaded_action' );
	add_action( 'wp_ajax_ccvftp', 'ccvftp_ajax_action' );// AJAX for registered users.
	add_action( 'wp_ajax_nopriv_ccvftp', 'ccvftp_ajax_action' );// AJAX for unregistered users.

	add_shortcode( 'custom_cv_form', 'ccvftp_shortcode_action' );//shortcode [custom_cv_form]

	function ccvftp_shortcode_action( $atts, $content ) {
		return $content;
	}

	function ccvftp_wp_action() {
		if ( ! is_admin() ) {
			global $post;
			if ( has_shortcode( $post->post_content,
				'custom_cv_form' ) ) { //enque sqcripts only when find proper shortcode!
				add_action( 'wp_enqueue_scripts',
					function () {
						wp_enqueue_style( 'bootstrap',
							'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' ); // bootstrap.
						wp_enqueue_script( 'ccvftp-js', plugins_url( 'scripts.js', __FILE__ ), // js script path.
							array( 'jquery', ), '1.0', true );
						$variables = array(
							'plugin_acronym' => 'ccvftp',
							'ajax_url'       => admin_url( 'admin-ajax.php' ),// ajax solver.
							'site_url'       => home_url(),// for redirect
						);
						wp_register_script( 'modaljs', plugins_url( 'custom-cv-form-technopreacher/modals.js' ),
							array( 'jquery' ), '1',
							true );

						wp_register_style( 'modalcss', plugins_url( 'custom-cv-form-technopreacher/modals.css' ), '',
							'', 'all' );

						wp_enqueue_script( 'modaljs' );
						wp_enqueue_style( 'modalcss' );

						wp_localize_script( 'ccvftp-js', 'obj', $variables );// AJAX-url to frontage into 'obj' object.
					}
				);
			}
		}
	}

	function ccvftp_plugins_loaded_action() {
		load_plugin_textdomain( 'ccvftp', false, 'ccvftp_domain' );
	}

	function ccvftp_deactivation_hook_action() {
		unload_textdomain( 'ccvftp_domain' );
	}

	function ccvftp_activation_hook_action() {
		// TODO some activation actions.
	}

	function ccvftp_ajax_action() {

		$make_form = filter_var( $_POST['first_time'] ?? false, FILTER_VALIDATE_BOOLEAN );//make bool from string.

		if ( ! $make_form ) { //chek nonce only if form is present on page.
			check_ajax_referer( 'ccvftp_nonce', 'security' );// return 403 if not pass nonce verification.
		}

		if ( $make_form ) {
			$nonce = wp_nonce_field( 'ccvftp_nonce', '_wpnonce', true, false );

			$modal = '
			<!-- trigger modal -->
			<a id ="but" hidden class="btn btn-primary btn-lg" href="#myModal1" data-toggle="modal">Launch demo modal</a>
			<!-- modal form-->
			<div id="myModal1" class="modal fade" tabindex="-1">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-body" id="modal-body">' . __( 'Thank you. Our managers will contact you during the day',
					'ccvftp_domain' ) . '</div>
					</div>
				</div>
			</div>';

			$form_html = "
			<form method='post' action='' id = 'login_cv_form'>
			<label for='login_name'>" . __( 'Name:', 'ccvftp_domain' ) . "</label><br>
			<input type='text' id='login_name' name='login_name' required><br>
			<label for='login_surname'>" . __( 'Surname:', 'ccvftp_domain' ) . "</label><br>
			<input type='text' id='login_surname' name='login_surname' required><br>
			<label for='login_mail'>" . __( 'E-mail:', 'ccvftp_domain' ) . "</label><br>
			<input type='email' id='login_mail' name='login_mail' required><br>
				<div id = 'uploaded-file-div'>
			<label>  </label><br>	" . "<input name='uploaded_file' id='uploaded_file' type='file' accept='.pdf,.doc,.docx' >
			</div>
			<button class='col mr-3' type='button' id='upload_button'>" . __( 'Upload', 'ccvftp_domain' ) . "</button>
		
		</form>
			";

			$html = " 
 			<div id = \"form-cv-div\" style=\"width:100%;height:100%;border:4px solid blue;\"> 
    		<p> $nonce.$form_html </p>
    		<p> $modal </p>
 			</div>
 			";
		} else { //proceed data from form.

			$validated = false;

			$SizeValidated    = false;//проверка размера
			$TypeValidated    = false;//проверка типа
			$emailValidated   = false;//проверка почты
			$nameValidated    = false;//проверка имени
			$surnameValidated = false;//проверка фамилии

			$html                         = '';

			// fields.
			$name_from_form    = sanitize_text_field( wp_unslash( $_POST['login_name'] ?? '' ) );
			$surname_from_form = sanitize_text_field( wp_unslash( $_POST['login_surname'] ?? '' ) );
			$mail_from_form    = sanitize_text_field( wp_unslash( $_POST['login_mail'] ?? '' ) );

			// file.
			$local_name = $_FILES["uploaded_file"]["name"] ?? '';    // basename() может предотвратить атаку на файловую систему;// может быть целесообразным дополнительно проверить имя файла
			$local_mame = basename( $local_name );

			$nameValidated    = (bool) preg_match( "#^[A-Za-zа-яА-Я\-_]+$#", $name_from_form );
			$surnameValidated = (bool) preg_match( "#^[A-Za-zа-яА-Я\-_]+$#", $surname_from_form );
			$emailValidated   = (bool) filter_var( $mail_from_form, FILTER_VALIDATE_EMAIL );

			if ( $_FILES["uploaded_file"]["size"] > 0 ) {
				if ( $_FILES["uploaded_file"]["size"] <= 5242880 ) {
					$SizeValidated = true;//валидация размера
				}

				move_uploaded_file( ( $_FILES["uploaded_file"]["tmp_name"] ?? '' ),
					wp_upload_dir()['basedir'] . '/' . $local_mame ); // move file to local storage!

				$mimes = array(
					'doc'  => 'application/msword',
					'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'pdf'  => 'application/pdf'
				);

				$filetype = wp_check_filetype( wp_upload_dir()['basedir'] . '/' . $local_mame, $mimes );

				if ( $filetype['type'] != false ) {
					$TypeValidated = true;
				}

			};

			$validated = (bool) ( $SizeValidated &
			                      $TypeValidated &
			                      $emailValidated &
			                      $nameValidated &
			                      $surnameValidated );

			$data      = array(
				'name_from_form'    => $name_from_form,
				'surname_from_form' => $surname_from_form,
				'mail_from_form'    => $mail_from_form
			);

			if ( $validated ) {

				add_action( 'phpmailer_init', function ( $phpmailer ) {
					$phpmailer->isSMTP();
					$phpmailer->Host       = 'smtp.gmail.com';
					$phpmailer->Port       = '587';
					$phpmailer->SMTPSecure = 'tls';
					$phpmailer->SMTPAuth   = true;
					$phpmailer->Username   = 'nik.nik.sulima@gmail.com';
					$phpmailer->Password   = 'qyozxcqdgkzqhmqd';
					$phpmailer->From       = 'nik.nik.sulima@gmail.com';// $data['mail_from_form'];
					$phpmailer->FromName   = 'admin';
				} );

				$to      = 'nik.nik.sulima@gmail.com';
				$subject = 'system';
				$message = " <div><b>NEW CV UPLOADED!</b><br> name: " . $data['name_from_form']
				           . "<br> surname: " . $data['surname_from_form']
				           . "<br> mail: " . $data['mail_from_form'] . "<br> CV: </div>";

				$attachments = array( wp_upload_dir()['basedir'] . '/' . $local_name );
				$headers     = array( 'Content-Type: text/html; charset=UTF-8' );

				global $ccvftp_mail_result;
				try {
					$ccvftp_mail_result =
						wp_mail( $to, $subject, $message, $headers, $attachments );
				} catch ( Exception $e ) {
					$ccvftp_mail_result = false;
				}
			}

			global $ccvftp_mail_result;
			$ccvftp_mail_result = true;
			if ( $ccvftp_mail_result == true ) {
				$html = "<div> <b> your CV is sended to our team :) </b></div>";
			}
			if ( $ccvftp_mail_result == false ) {
				$html = "<div> <b> oops! some errors under CV send :( </b></div>";
			}
		}

		$data = array(
			'html' => $html,
		);

		wp_reset_postdata();
		wp_send_json_success( $data );
		die();
	}

	;