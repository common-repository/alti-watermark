<?php
class Alti_Watermark_Admin {

	private $version;
	private $messages = array();
	private $size;

	/**
	 * constructor
	 * @param string $version
	 */
	public function __construct( $version ) {

		$this->version = $version;

	}

	/**
	 * Add submenu to left page in admin
	 */
	public function add_submenu_page() {
		add_submenu_page( 'upload.php', 'alti-watermark', 'Watermark <span class="dashicons dashicons-awards"></span>', 'manage_options', 'alti-watermark' . '-settings-page', array($this, 'render_settings_page') );
	}

	/**
	 * Render settings page for plugin
	 */
	public function render_settings_page() {
		require plugin_dir_path( __FILE__ ) . 'views/' . 'alti-watermark' . '-admin-settings-page.php';
	}

	/**
	 * prepare enqueue styles for wordpress hook
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'alti-watermark', plugin_dir_url( __FILE__ ) . 'assets/css/alti-watermark-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * prepare enqueue scripts for wordpress hook
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'alti-watermark', plugin_dir_url( __FILE__ ) . 'assets/js/alti-watermark-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * check if mod rewrite is active
	 * @return message messages
	 */
	public function check_modrewrite() {
		if( !is_admin() ) exit;
		if( function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
			$this->messages['apache'][] = array(
					'message' => __('The Watermark plugin cannot work without the Rewrite Apache Module (<a href="http://httpd.apache.org/docs/current/en/mod/mod_rewrite.html" target="_blank">mod_rewrite</a>). Yourself or your web host has to activate this module.', 'alti-watermark'),
					'type' => 'error',
					'id' => '1'
				);
		}
	}

	/**
	 * Check if a previous htaccess exists
	 * @return message messages
	 */
	public function check_previous_htaccess() {
		if( !is_admin() ) exit;
		if( file_exists(self::get_uploads_dir().'/'.'.htaccess') ) {
			if( !preg_match('/[plugin_name=' . 'alti-watermark' . ']/im', file_get_contents( self::get_uploads_dir().'/'.'.htaccess' )) ) {

				$this->messages['system'][] = array(
					'message' => __('A previous htaccess file exists already, it has to be renamed.', 'alti-watermark'),
					'type' => 'updated-nag'
				);

				if( rename( self::get_uploads_dir().'/'.'.htaccess', self::get_uploads_dir().'/'. 'alti-watermark' . '.previous.htaccess' ) ) {
					$this->messages['file'][] = array(
							'message' => __('The previous htaccess file has been renamed successfully to ', 'alti-watermark') . 'alti-watermark' . '.previous.htaccess.',
							'type' => 'updated'
						);
				} else {
					$this->messages['system'][] = array(
						'message' => __('The previous htaccess file couldn\'t be renamed.', 'alti-watermark'),
						'type' => 'error',
						'id' => '10'
					);
				}
			}
		}

	}



	/**
	 * check if watermark folder is accessible and writable ...
	 * @return bool true or false ;)
	 */
	public function check_watermark_folder() {
		if( !is_admin() ) exit;
		// check if folder-data exists
		if( !file_exists( WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data' ) ) {
			if( !mkdir( WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data', 0755, true ) ) {
				$this->messages['system'][] = array(
						'message' => __('Impossible to create the folder needed to store the watermark image. The directory : ', 'alti-watermark') . WP_PLUGIN_DIR. __(' has to be writable. <br> Please change <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">file permissions</a> in order to let the plugin works.', 'alti-watermark'),
						'type' => 'error',
						'id' => '9'
					);
				return 'false';
			} else { return 'true'; }

		}

		// check if is writable folder-data
		if( !is_writable( WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data' )) {
			if ( !chmod( WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data', 0755 )) {
				$this->messages['system'][] = array(
						'message' => __('Impossible to create the folder needed to store the watermark image. The directory : ', 'alti-watermark') . WP_PLUGIN_DIR. __(' has to be writable. <br> Please change <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">file permissions</a> in order to let the plugin works.', 'alti-watermark'),
						'type' => 'error',
						'id' => '8'
					);
				return 'false';
			} else { return 'true'; }
		}

	}

	/**
	 * save settings of options page
	 * @return message messages
	 */
	public function save_settings() {

		if( !is_admin() ) exit;

		if( isset($_POST['sizes']) ) $_post_sizes = $_POST['sizes'];

		if( isset($_post_sizes) && (is_array($_post_sizes) && count($_post_sizes) > 0) ) {
			// sanitize _post_sizes
			foreach ($_post_sizes as $size_index => $size_name) {
				if( !preg_match('/[a-z0-9-_]/i', $size_name) ) {
					unset($_post_sizes[$size_index]);
				} else {
					$_post_sizes[$size_index] = sanitize_text_field( $size_name );
				}
			}
			$this->sizes = $_post_sizes;
			$this->messages['size'][] = array(
					'message' => __('Image formats have been updated.', 'alti-watermark'),
					'type' => 'updated'
				);
		}
		if( !isset($_post_sizes)) {
			$this->sizes = array('fullsize');
			$this->messages['size'][] = array(
					'message' => __('You have to choose at least one image format. The fullsize has been set per default.', 'alti-watermark'),
					'type' => 'error',
					'id' => '7'
				);
		}

		if( !empty($_FILES['watermarkFile']['name']) ) {

			$_files_watermarkfile = $_FILES['watermarkFile'];

				// convert watermark to png
				if( substr($_files_watermarkfile['name'], -4 ) != '.png' && !@imagepng(imagecreatefromstring(file_get_contents($_files_watermarkfile['tmp_name'])), WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data' . '/watermark.png') ) {
					$this->messages['file'][] = array(
							'message' => __('Impossible to upload and/or generate the watermark image.', 'alti-watermark'),
							'type' => 'error',
							'id' => '6'
						);
				}
				// conserve clean aspect of png
				if( substr($_files_watermarkfile['name'], -4 ) == '.png' && !@move_uploaded_file( $_files_watermarkfile['tmp_name'], WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data' . '/watermark.png' ) ) {

					$this->messages['file'][] = array(
							'message' => __('Impossible to upload and/or generate the watermark image.', 'alti-watermark'),
							'type' => 'error',
							'id' => '61'
						);
				}

				if( !preg_match('/^image/i', $_files_watermarkfile['type']) || $_files_watermarkfile['error'] != 0 ) {
					$this->messages['file'][] = array(
							'message' => __('Watermark cannot be uploaded because it is not a valid image file.', 'alti-watermark'),
							'type' => 'error',
							'id' => '5'
						);
				}

				// Message de validation
				if (!array_key_exists('file', $this->messages)) {
					$this->messages['file'][] = array(
							'message' => 'Watermark has been uploaded correctly',
							'message' => __('Watermark image has been uploaded successfully !', 'alti-watermark'),
							'type' => 'updated'
						);
				}

		}

	}

	/**
	 * display messages manager
	 * @return array push array messages in to partial view
	 */
	public function display_messages() {
		if( !is_admin() ) exit;
		foreach ($this->messages as $name => $messages) {
			foreach ($messages as $message) {
				require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/includes/alti-watermark-admin-message.php';
			}
		}

	}

	/**
	 * generate htaccess file
	 * @return file create htaccess file or return error message
	 */
	public function generate_htaccess() {
		if( !is_admin() ) exit;

		$widthRegex       = '';
		$widthData        = '';
		$widthDynamic     = '';
		$htaccessContent  = '';
		$widths           = self::set_watermark_width();
		$uploads_url      = self::get_uploads_url();
		$uploads_dir      = self::get_uploads_dir();
		$plugin_url       = WP_PLUGIN_URL;
		$plugin_dir       = WP_PLUGIN_DIR;
		$relative_path_uploads_to_plugin = self::get_relative_path($uploads_dir, $plugin_dir);
		$date             = date('Y-m-d H:i.s');
		$phpv             = phpversion();
		$_post_cropped    = $_POST['cropped'];

		// creation of width Regular Expression
		if( !empty($widths) ) {
			$widthRegex .= '(.*';
			foreach ($widths as $width) {
				$widthDynamic = explode('x', $width);
				if($width == $widths[0] && $width != 'fullsize') $widthRegex .= '(';
				if( $width != 'fullsize' && in_array($width, $_post_cropped) == 1 ) $widthRegex .= '-' . $width;
				if( $width != 'fullsize' && in_array($width, $_post_cropped) == 0 ) {

					if( intval($widthDynamic[0]) > 0 && intval($widthDynamic[1]) > 0 ) {
						$widthRegex .= '-([1-' . $widthDynamic[0][0] . '][\d]{1,' . intval(strlen($widthDynamic[0])-1) . '}|[\d]{1,' . intval(strlen($widthDynamic[0])-1) . '})x' . $widthDynamic[1];
						$widthRegex .= '|-'. $widthDynamic[0] . 'x([1-' . $widthDynamic[1][0] . '][\d]{1,' . intval(strlen($widthDynamic[1])-1) . '}|[\d]{1,' . intval(strlen($widthDynamic[0])-1) . '})';

					}
					if( intval($widthDynamic[0]) == 0 && intval($widthDynamic[1]) > 0 ) {
						$widthRegex .= '-([\d]+)x' . $widthDynamic[1];
					}
					if( intval($widthDynamic[1]) == 0 && intval($widthDynamic[0]) > 0 ) {
						$widthRegex .= '-'. $widthDynamic[0] . 'x([\d]+)';
					}

				}
				if( $width == 'fullsize' ) $widthRegexFullscreen = true;
				$widthData .= $width;
				if( end($widths) != $width ) {
					$widthRegex .= '|';
					$widthData .= '|';
				}
			}
			// if( $widthDynamic[0] > 0 ) $widthRegex .= '##'.$widthDynamic[0].'##';
			$widthRegex = rtrim($widthRegex, '|') . ')\\.jpg';
			if(empty($widthRegexFullscreen)) $widthRegex .= ')';
			if(!empty($widthRegexFullscreen)) {
				$widthRegex .= '|.*(?<!-\dx\d)(?<!-\d\dx\d)(?<!-\dx\d\d)(?<!-\d\dx\d\d)(?<!-\d\d\dx\d\d)(?<!-\d\dx\d\d\d)(?<!-\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d)(?<!-\d\d\dx\d\d\d\d)(?<!-\d\d\d\dx\d\d\d\d)(?<!-\d\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d)(?<!-\d\d\d\d\dx\d\d\d\d\d)\.jpg)';
			}
			if(count($widths) == 1 && $widths[0] == 'fullsize') {
				$widthRegex = '(.*(?<!-\dx\d)(?<!-\d\dx\d)(?<!-\dx\d\d)(?<!-\d\dx\d\d)(?<!-\d\d\dx\d\d)(?<!-\d\dx\d\d\d)(?<!-\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d)(?<!-\d\d\dx\d\d\d\d)(?<!-\d\d\d\dx\d\d\d\d)(?<!-\d\d\d\dx\d\d\d)(?<!-\d\d\d\dx\d\d)(?<!-\d\d\d\d\dx\d\d\d\d\d)(?<!-\d\d\d\dx\d\d\d\d\d)(?<!-\d\d\d\d\dx\d\d\d\d)(?<!-\d\d\dx\d\d\d\d\d)(?<!-\d\d\d\d\dx\d\d\d)\.jpg)';
			}
			$widthRegex .= '{1}((\?|\&)([^\.\?\ ]+))*'; // parameters url
		}

		// creation of htaccess file content
		// \n separator is important for htaccess file
		$htaccessContent   .= "\n# BEGIN " . 'alti-watermark' . " Plugin\n";
		$htaccessContent  .= "<ifModule mod_rewrite.c>\n";
		$htaccessContent  .= "\tRewriteEngine on\n";
		$htaccessContent  .= "\tRewriteCond %{REQUEST_FILENAME} -f\n";
		$htaccessContent  .= "\tRewriteRule ^{$widthRegex}$ {$relative_path_uploads_to_plugin}alti-watermark/public/views/alti-watermark-public-bridge.php?imageRequested=$1 [PT]\n";
		$htaccessContent  .= "</ifModule>\n";
		$htaccessContent  .= "# [date={$date}] [php={$phpv}] [width={$widthData}] [plugin_name=" . 'alti-watermark' . "] [version={$this->version}]\n";
		$htaccessContent  .= "# END " . 'alti-watermark' . " Plugin\n";

		if( !file_put_contents( self::get_uploads_dir().'/'.'.htaccess', $htaccessContent ) ) {
			$this->messages['file'][] = array(
					'message' => 'Impossible to create or modified the htaccess file.',
					'type' => 'error',
					'id' => '4'
				);
		}

	}

	/**
	 * return sizes
	 */
	public function set_watermark_width() {
		return $this->sizes;
	}

	/**
	 * get widths of images in which the watermark is applyed
	 * @return array name of formats
	 */
	public function get_watermark_width() {
		if( !is_admin() ) exit;
		if( file_exists((self::get_uploads_dir().'/'.'.htaccess')) && file_get_contents(self::get_uploads_dir().'/'.'.htaccess') ) {

			if(  preg_match('/\[width=([a-z0-9|]+)\]/i', file_get_contents(self::get_uploads_dir().'/'.'.htaccess'), $matches) ) {
				if(is_array(explode('|', $matches[1]))) {
					return explode('|', $matches[1]);
				}
				else {
					return array($matches[1]);
				}
			}
			else {
				$this->messages['file'][] = array(
					'message' => __('Impossible to get the image formats setting.', 'alti-watermark'),
					'type' => 'error',
					'id' => '3'
				);
				return array('fullsize'); // if htaccess exists but width setting is not writable
			}

		}
		else {
			return array('fullsize');
		}
	}

	/**
	 * get uploads dir
	 * @return string return path
	 */
	public function get_uploads_dir() {
		$uploads_dir = wp_upload_dir();
		return $uploads_dir['basedir'];
	}

	/**
	 * check uploads url
	 * @return message return message
	 */
	public function check_uploads_url() {
		if( !is_admin() ) exit;
		if(preg_match('/(\.\.\/)/i', self::get_uploads_url())) {
			$this->messages['file'][] = array(
				'message' => __('Your uploads directory seems to be customized. It uses path that is not supported or not valid. The plugin will not work properly.', 'alti-watermark') . '<br><code>'. self::get_uploads_url() . '</code>',
				'type' => 'error',
				'id' => '11'
			);
		}
	}

	/**
	 * get uploads folder url
	 * @return string return full url
	 */
	public function get_uploads_url() {
		if( !is_admin() ) exit;
		$uploads_dir = wp_upload_dir();
		return $uploads_dir['baseurl'];
	}

	/**
	 * Get relative path
	 * http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
	 */
	function get_relative_path($from, $to)
	{
		if( !is_admin() ) exit;
	    // some compatibility fixes for Windows paths
	    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
	    $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
	    $from = str_replace('\\', '/', $from);
	    $to   = str_replace('\\', '/', $to);

	    $from     = explode('/', $from);
	    $to       = explode('/', $to);
	    $relPath  = $to;

	    foreach($from as $depth => $dir) {
	        // find first non-matching dir
	        if($dir === $to[$depth]) {
	            // ignore this directory
	            array_shift($relPath);
	        } else {
	            // get number of remaining dirs to $from
	            $remaining = count($from) - $depth;
	            if($remaining > 1) {
	                // add traversals up to first matching dir
	                $padLength = (count($relPath) + $remaining - 1) * -1;
	                $relPath = array_pad($relPath, $padLength, '..');
	                break;
	            } else {
	                $relPath[0] = './' . $relPath[0];
	            }
	        }
	    }
	    return implode('/', $relPath);
	}

	/**
	 * check if gd library exists
	 * @return add error message to messages array.
	 */
	public function check_gd_library() {
		if( !is_admin() ) exit;
		if ( !function_exists('gd_info') && !extension_loaded('gd') ) {
			$this->messages['file'][] = array(
					'message' => __('The PHP GD Library to manipulate images has not been found on your server.', 'alti-watermark'),
					'type' => 'error',
					'id' => '2'
				);
		}
	}

	/**
	 * return array of watermark size
	 * @return array
	 */
	public function get_watermark_size() {
		$image = getimagesize( WP_PLUGIN_DIR . '/' . 'alti-watermark' . '-data' . '/watermark.png' );
		return $image;
	}
	/**
	 *
	 * Get differents image size defined by wordpress and theme
	 * Inspired by :http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
	 *
	**/
	public function get_image_sizes( $size = '' ) {
		if( !is_admin() ) exit;
        global $_wp_additional_image_sizes;

        $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach( $get_intermediate_image_sizes as $_size ) {

                if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {

                        $sizes[ $_size ]['name'] = $_size;
                        $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
                        $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
                        $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

                } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

                        $sizes[ $_size ] = array(
                                'name' => $_size,
                                'width' => $_wp_additional_image_sizes[ $_size ]['width'],
                                'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                                'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
                        );

                }

        }

        // Get only 1 size if found
        if ( $size ) {

                if( isset( $sizes[ $size ] ) ) {
                        return $sizes[ $size ];
                } else {
                        return false;
                }

        }

        return $sizes;
	}

	public function render_image_sizes($image_size) {
		require plugin_dir_path( __FILE__ ) . 'views/includes/' . 'alti-watermark' . '-admin-image-size-label.php';
	}

	/**
	 * add a settings link to plugin page.
	 * @param string $links array of links
	 */
	public function add_settings_link( $links ) {
	    $settings_link = '<a href="upload.php?page=' . 'alti-watermark' . '-settings-page">' . __( 'Settings' ) . '</a>';
	    array_unshift($links, $settings_link);
	  	return $links;
	}

}
