<?php
/**
 * Export WordPress posts and fields to CSV main class
*
* @author yann@abc.fr
* @see: https://github.com/Celyan-SAS/export-wordpress-posts-and-fields-to-csv
*
*/
class wpExportPFCSV {
	
	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		
		/** Plugin admin page for export button **/
		add_action( 'admin_menu', array( $this, 'plugin_admin_add_page' ) );
		
		/** Hijack admin display **/ 
		add_action( 'admin_init', array( $this, 'hijack' ));
		
		/** Load i18n **/
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}
	
	/**
	 * Adds the plugin settings page
	 * 
	 */
	public function plugin_admin_add_page() {

		add_menu_page( 
			__( 'Export WP posts and fields to CSV', 'wpexportpfcsv' ),	// Page title
			__( 'Export to CSV', 'wpexportpfcsv' ), 					// Menu title
			'manage_options', 											// Capability
			'wpexportpfcsv', 											// Menu slug
			array( $this, 'plugin_options_page'	)						// Method
		);
	}
	
	/**
	 * Pugin admin page for export button
	 * 
	 */
	public function plugin_options_page() {
		
		$post_types = get_post_types( array(), 'names' );
		?>
		<h2><?php _e( 'Export WordPress posts and fields to CSV', 'wpexportpfcsv' ); ?></h2>
		<form>
		<select name="post_type">
		<?php foreach( $post_types as $post_type ) : ?>
			<option><?php echo $post_type; ?></option>
		<?php endforeach; ?>
		</select>
		<input type="submit" name="action" value="<?php _e( 'Export', 'wpexportpfcsv' ); ?>" class="wpexportpfcsv" />
		<input type="hidden" name="page" value="<?php echo htmlentities($_GET['page']); ?>" />
		</form>
		<?php
	}
	
	/**
	 * Hijack admin headers to launch download
	 * 
	 */
	public function hijack() {
		if( !empty( $_GET['action'] ) && __( 'Export', 'wpexportpfcsv' ) == $_GET['action'] ) {
			$this->export();
		}
	}
	
	/**
	 * Generation of export dump
	 * 
	 */
	private function export() {
		global $wpdb;
		
		$post_type = 'post';
		if( !empty( $_GET['post_type'] ) ) {
			$post_type = sanitize_text_field( $_GET['post_type'] );
		}
		$data = '';
		
		if( $posts = get_posts( array( 'post_type'=>$post_type, 'posts_per_page'=>-1 ) ) ) {
			$acf_fields_a = get_fields( $posts[0]->ID );

			// echo '<pre>ACF fields:<br/>'; var_dump( $fields ); echo '</pre>'; // DEBUG
			
			$header_fields_a = array(
				'ID',
				'post_title'
			);
			if( !empty( $acf_fields_a ) && is_array( $acf_fields_a ) ) {
				$header_fields_a = array_merge( $header_fields_a, array_keys( $acf_fields_a ) );
			}
			
			foreach( $posts as $post ) {
				
				$line = '';
				
				/** Post ID and post title first **/
				$line .= $post->ID . ';';
				$value = str_replace( '"' , '""' , $post->post_title );
				$value = '"' . $value . '"' . ";";
				$line .= $value;
				
				/** All ACF fields next **/
				if( !empty( $acf_fields_a ) && is_array( $acf_fields_a ) ) {
					foreach( array_keys( $acf_fields_a ) as $acf_field ) {
						$value = get_field( $acf_field, $post->ID );
						if ( ( !isset( $value ) ) || ( $value == "" ) ) {
							$value = ";";
						} elseif( is_object( $value ) ) {
							$value = '"*OBJECT*"' . ";";
						} elseif( is_array( $value ) ) {
							$value_s = '';
							foreach( $value as $k => $val ) {
								if( is_array( $val ) ) {
									foreach( $val as $key => $v ) {
										$key = str_replace( '"' , '""' , $key );
										$v = str_replace( '"' , '""' , $v );
										$v = preg_replace( '/<br\s*\/?>\r?\n/i', "\n", $v );
										if( !is_array( $v ) ) {
											$value_s .= $key. ': ' . strip_tags( html_entity_decode( $v ) ) . "\n";
										} else {
											$value_s .= $key. ': *SERIALIZED/ARRAY*' . "\n";
										}
									}
								} else {
									$val = str_replace( '"' , '""' , $val );
									$val = preg_replace( '/<br\s*\/?>\r?\n/i', "\n", $val );
									$value_s .= strip_tags( html_entity_decode( $val ) ) . "\n";
								}
							}
							$value = '"' . $value_s . '"' . ";";
						} else {
							$value = str_replace( '"' , '""' , $value );
							$value = preg_replace( '/<br\s*\/?>\r?\n/i', "\n", $value );
							$value = '"' . strip_tags( html_entity_decode( $value ) ) . '"' . ";";
						}
						$line .= $value;
					}
				}
				$data .= trim( $line ) . "\r\n";
			}
			
			$header = implode( ';', $header_fields_a );
			
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=export.csv");
			header("Pragma: no-cache");
			header("Expires: 0");
			print "$header\r\n$data";
			
			exit;
			
		} else {
			echo '<div class="results"><p>La requête n\'a retourné aucun résultat.</p></div>';
		}
	}
	
	/**
	 * Load the text translation files
	 * 
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wpexportpfcsv', false, plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
	}
}
?>