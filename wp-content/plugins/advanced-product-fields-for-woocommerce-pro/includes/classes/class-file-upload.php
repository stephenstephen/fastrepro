<?php

namespace SW_WAPF_PRO\Includes\Classes {

	use SW_WAPF_PRO\Includes\Models\Field;

	class File_Upload {

		private static $field_for_upload = null;
		public static $upload_parent_dir = 'wapf';

		private static $disallow = [
			'exe','bat','cmd','php','php3','php4','php5','cgi','dll','js','jar','app','vb','vbscript','htaccess','htpasswd','msi','sh','shs','bin','asp','cer','csr','sys','jar','pif'
		];

		public static function can_upload() {
			$needs_login = get_option('wapf_settings_upload_login','no');
			if( $needs_login === 'yes' && ! is_user_logged_in() )
				return false;

			return true;
		}

		public static function get_htaccess_rules() {

			$filetypes = '';
			$restricted_filetypes =  self::get_allowed_filetypes(self::$field_for_upload);
			if( ! empty( $restricted_filetypes ) && is_array( $restricted_filetypes ) ) {
				$filetypes = join( '|', Enumerable::from($restricted_filetypes)->select(function($v,$k){return $k;})->toArray() );
			}
			$rules = "Options -Indexes\n"; 
			$rules .= "<Files ~ '.*\..*'>\n";
			$rules .= "Order Allow,Deny\n";
			$rules .= "Deny from all\n";
			$rules .= "</Files>\n";
			$rules .= "<FilesMatch '\.(" . $filetypes . ")$'>\n";
			$rules .= "Order Deny,Allow\n";
			$rules .= "Allow from all\n";
			$rules .= "</FilesMatch>";

            return apply_filters('wapf/htaccess_content', $rules, self::$field_for_upload, $restricted_filetypes );

		}

		public static function get_base_upload_dir() {
			$wp_upload_dir = wp_upload_dir();
			$path = trailingslashit($wp_upload_dir['basedir']) . trailingslashit(self::$upload_parent_dir);
			return $path;
		}

		public static function create_protection_files($upload) {
			if(!file_exists($upload['parent_path'])) {
				wp_mkdir_p($upload['parent_path']);
				@file_put_contents($upload['parent_path'] . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.');
				@file_put_contents($upload['parent_path'] . '/.htaccess', 'Options -Indexes');
			}

			if(!file_exists($upload['path'])) {
				wp_mkdir_p($upload['path']);
				@file_put_contents($upload['path'] . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.');
			}

			$htaccess = self::get_htaccess_rules();
			@file_put_contents($upload['path'] . '/.htaccess', $htaccess);

		}

		public static function set_upload_dir($upload) {

			$parent = '/' . self::$upload_parent_dir;
			$customer_id = md5( WC()->session->get_customer_id());

			$upload['subdir'] = $parent . '/' . self::$field_for_upload->id . '/' . $customer_id; 
			$upload['path']         = $upload['basedir'] . $upload['subdir'];
			$upload['parent_path']  = $upload['basedir'] . $parent;
			$upload['url']          = $upload['baseurl'] . $upload['subdir'];

			self::create_protection_files($upload);

			return $upload;
		}

		public static function handle_files_array($field_groups,$files) {

			$fields = Enumerable::from($field_groups)->merge(function($x){return $x->fields; })->toArray();

			foreach ($files as $key => &$files_arr) {

				$key = explode('_',str_replace('field_','',$key))[0];
				$field = Enumerable::from($fields)->firstOrDefault(function($x) use ($key) {return $x->id === $key;});

				if(!$field) continue;

				foreach($files_arr as &$file) {
					if(empty($file['name']) || isset($file['uploaded_file']))
						continue;

					$upload = File_Upload::handle_upload($file, $field);
					$upload = apply_filters('wapf/file/upload_result', $upload, $field);

					if(empty($upload['error'])) {
						$file['uploaded_file'] = $upload['url'];
						$file['uploaded_file_path'] = $upload['file'];
						$file['field'] = $field->id;
					} else {
						return sprintf(apply_filters('wapf/message/file_upload_error', __( "Error uploading file \"%s\". %s", 'sw-wapf' )),$file['name'], $upload['error']);
					}
				}
			}

			return $files;
		}

		public static function handle_upload($file, Field $field) {

			if (!function_exists('wp_handle_upload'))
				require_once(ABSPATH . 'wp-admin/includes/file.php');
			include_once(ABSPATH . 'wp-admin/includes/media.php');
			$allowed_types = self::get_allowed_filetypes($field);
			$file_info = wp_check_filetype_and_ext($file['tmp_name'],$file['name'], $allowed_types );

			if(!self::can_upload()) {
				return ['error' => apply_filters('wapf/message/file_upload_logged_in', __( 'You are not authorized to upload files.', 'sw-wapf' )) ];
			}
			
			if(empty($file_info['type']) && !$file['type'] == 'model/stl') {
				return ['error' =>  apply_filters( 'wapf/message/file_not_valid', __( 'The uploaded file type is not allowed.', 'sw-wapf' )) ];
			}

			self::$field_for_upload = $field;
			add_filter( 'upload_dir', ['SW_WAPF_PRO\Includes\Classes\File_Upload','set_upload_dir'] );
			$upload = wp_handle_upload(
				$file,
				[
					'test_form' => false,
					'mimes'		=> $allowed_types
				]
			);
			remove_filter( 'upload_dir', ['SW_WAPF_PRO\Includes\Classes\File_Upload','set_upload_dir'] );

			return $upload;
		}

		public static function create_uploaded_file_array() {

			if(!isset($_FILES['wapf']))
				return [];

			$result = [];

			foreach($_FILES['wapf']['name'] as $key => $content) {
				if(empty($content[0]))
					continue;

				$result[$key] = [];

				for($i=0; $i<count($content); $i++) {
					$result[$key][] = [
						'name'      => $content[$i],
						'tmp_name'  => $_FILES['wapf']['tmp_name'][$key][$i],
						'size'  => $_FILES['wapf']['size'][$key][$i],
						'error'  => $_FILES['wapf']['error'][$key][$i],
						'type'  => $_FILES['wapf']['type'][$key][$i]
					];
				}

			}

			return $result;

		}

		public static function get_allowed_filetypes(Field $field) {

			$all = self::get_all_allowed_filetypes();

			if(empty($field->options['accept']))
				return $all;

			$types = explode(',', $field->options['accept']);
			$t = [];

			foreach ($types as $type) {
				if(!isset($all[$type]))
					continue;

				$type = sanitize_text_field($type);
				$t[$type] = $all[$type];
			}

			return $t;

		}

		public static function get_all_allowed_filetypes() {
			$all = get_allowed_mime_types();

			foreach(self::$disallow as $d){
				unset($all[$d]);
			}

			$all['stl'] = "model/stl";

			return $all;
		}

		public static function validate_files_from_ajax($files, $field_groups) {
			$fields = Enumerable::from($field_groups)->merge(function($x){return $x->fields; })->toArray();

			if(empty($files))
				return true;

			foreach($files as $key => $content) {
				$field_id = explode('_clone_',$key)[0];
				$field_id = str_replace('field_','',$field_id);

				$field = Enumerable::from($fields)->firstOrDefault(function($x) use($field_id) {return $x->id === $field_id; });
				if(!$field)
					return apply_filters('wapf/message/file_upload_nofield', __( "This file doesn't belong to any product option.", 'sw-wapf' ));

				$err = self::validate_files_for_field($files,$field);
				if(is_string($err))
					return $err;
			}

			return true;
		}

		public static function validate_ajax_upload_for_cart($raw_cart_val) {
			if(empty($raw_cart_val))
				return true;
			$split = explode(',',$raw_cart_val);
			$path = wp_upload_dir()['basedir'] . '/' . trailingslashit(self::$upload_parent_dir);

			foreach ($split as $f) {
				if(!file_exists($path . sanitize_text_field($f)))
					return sprintf(apply_filters('wapf/message/file_upload_error_general', __( "Error uploading file \"%s\". Please try again.", 'sw-wapf' )),$f);
			}
			return true;
		}

		public static function validate_files_for_field($files,Field $field,$clone_idx = 0) {
			if(empty($files))
				return true;

			$file_key = 'field_' . $field->id;

			if($clone_idx > 0)
				$file_key .= '_clone_' . $clone_idx;

			if(!isset($files[$file_key]))
				return true;

			if(!self::can_upload()) {
				return apply_filters('wapf/message/file_upload_logged_in', __( 'You are not authorized to upload files.', 'sw-wapf' ));
			}

			$total_files = count($files[$file_key]);

			if(empty($field->options['multiple']) && $total_files> 1) {
				$error = apply_filters('wapf/message/upload_err_too_many', __("You are not allowed to upload multiple files.",'sw-wapf'));
				return $error;
			}

			$max_size = floatval($field->get_option('maxsize',1)) * pow(1024,2); 
			$types = File_Upload::get_allowed_filetypes($field);

			$total_files_without_error = 0;

			for($i=0; $i<$total_files; $i++ ) {
				$name = $files[$file_key][$i]['name'];
				if($name != '') {
					if($files[$file_key][$i]['error'] > 0) {

						switch($files[$file_key][$i]['error']) {
							case 1: $error = apply_filters('wapf/message/upload_err_ini_size', __("The uploaded file exceeds the upload_max_filesize directive in php.ini.",'sw-wapf')); break;
							case 4: $error = apply_filters('wapf/message/upload_err_cant_write', __("Failed to write file to disk.",'sw-wapf')); break;
							case 3: $error = apply_filters('wapf/message/upload_err_partial', __('The uploaded file was only partially uploaded.', 'sw-wapf')); break;
							default: $error = apply_filters('wapf/message/upload_error_code', sprintf(__('Error code: %s','sw-wapf'), $files[$file_key][$i]['error'] )); break;
						}
						return sprintf(apply_filters('wapf/message/file_upload_error', __( "Error uploading file \"%s\". %s", 'sw-wapf' )),$files[$file_key][$i]['name'], $error);
					}

					if($files[$file_key][$i]['size'] > $max_size) {
						$error = apply_filters('wapf/message/upload_err_too_big', __("The filesize is too big.",'sw-wapf'));
						return sprintf(apply_filters('wapf/message/file_upload_error', __( "Error uploading file \"%s\". %s", 'sw-wapf' )),$files[$file_key][$i]['name'], $error);
					}

					// print_r($files[$file_key][0]['type']);
					// echo "<br>";
					// print_r(array_values($types));
					// exit;

					if(isset($files[$file_key][$i]['type']) && !in_array($files[$file_key][$i]['type'], array_values($types))) {
						$error = apply_filters('wapf/message/upload_err_type_unsupported', __("This file type is not supported.",'sw-wapf'));
						return sprintf(apply_filters('wapf/message/file_upload_error', __( "Error uploading file \"%s\". %s", 'sw-wapf' )),$files[$file_key][$i]['name'], $error);
					}

					$error = apply_filters('wapf/validate/file', ['error' => false], $files[$file_key][$i], $field);
					if ( $error['error'] ) {
						return isset( $error['message'] ) ? $error['message'] : '';
					}

					$total_files_without_error++;

				}
			}

			if( $total_files_without_error > intval(ini_get('max_file_uploads'))) {
				$error = apply_filters('wapf/message/upload_err_uploads_exceeded', __("The maximum number of allowed simultanious uploads was exceeded. Please upload less files.",'sw-wapf'));
				return $error;
			}

			return true;

		}

		public static function is_ajax_upload() {
			$enable_ajax = get_option( 'wapf_upload_ajax', 'no' );
			return apply_filters( 'wapf_upload_ajax', $enable_ajax === 'yes' ? true : false );
		}

        public static function delete_files( $files = [] ) {

            foreach ( $files as $file ) {
                unlink( $file );
            }

        }

		public static function create_zip( $files = [], $file_name = 'downloads' ) {

			if( ! class_exists( 'ZipArchive') ) {
				return false;
			}

			$valid_files = [];

			if( is_array( $files ) ) {
				foreach( $files as $file ) {

					if(file_exists($file)) {
						$valid_files[] = $file;
					}
				}
			}

			if( empty( $valid_files ) ) {
				return false;
			}

			if( count( $valid_files ) ) {

				$path = wp_upload_dir()['basedir'] . '/' . trailingslashit(self::$upload_parent_dir) . trailingslashit('zips');

				if( ! file_exists( $path ) ) {
					wp_mkdir_p($path);
					@file_put_contents( $path . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.' );
					@file_put_contents( $path['parent_path'] . '/.htaccess', 'Options -Indexes' );
				}

				$file_name = $path. $file_name . '.zip';
				$zip = new \ZipArchive();

				if ( $zip->open( $file_name,  (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)  ) ) {

					foreach ( $valid_files as $file ) {
						$zip->addFile( $file, basename($file) );
					}

					$zip->close();

					if(file_exists( $file_name )) {
						return $file_name;
					}

				}

			}

			return false;

		}
	}

}