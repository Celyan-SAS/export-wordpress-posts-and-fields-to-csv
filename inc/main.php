<?php
/**
 * Export WordPress posts and fields to CSV main class
*
* @author yann@abc.fr
* @see: https://github.com/Celyan-SAS/export-wordpress-posts-and-fields-to-csv
*
*/
class wpExportPFCSV {
	
	private $results = null;
	
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
		
		$capability = apply_filters( 'wpc_export_capability', 'manage_options' );

		add_menu_page( 
			__( 'Export WP posts and fields to CSV', 'wpexportpfcsv' ),	// Page title
			__( 'Export to CSV', 'wpexportpfcsv' ), 					// Menu title
			$capability, 												// Capability
			'wpexportpfcsv', 											// Menu slug
			array( $this, 'plugin_options_page'	)						// Method
		);
	}
	
	/**
	 * Pugin admin page for export button
	 * 
	 */
	public function plugin_options_page() {
		
		if( false === $this->results ) {
			echo '<div class="error"><p>';
			_e( 'This request did not return any result.', 'wpexportpfcsv' );
			echo '</p></div>';
		}
		
		$post_types = get_post_types( array(), 'names' );
		?>
		<div class="wrap">
		<h2><?php _e( 'Export WordPress posts and fields to CSV', 'wpexportpfcsv' ); ?></h2>
		<form>
		<select name="post_type">
			<option>WP_users</option>
		<?php foreach( $post_types as $post_type ) : ?>
			<option><?php echo $post_type; ?></option>
		<?php endforeach; ?>
		</select>
		<input type="submit" name="action" value="<?php _e( 'Export', 'wpexportpfcsv' ); ?>" class="wpexportpfcsv" />
		<input type="hidden" name="page" value="<?php echo htmlentities($_GET['page']); ?>" />
		</form>
		</div>
		<?php
	}
	
	/**
	 * Hijack admin headers to launch download
	 * 
	 */
	public function hijack() {
		if( !empty( $_GET['action'] ) && __( 'Export', 'wpexportpfcsv' ) == $_GET['action'] ) {
			if( $this->export() ) {
				exit;
			}
		}
	}
	
	private function exportwp_acf_keys_by_name_and_posttype($post_type){
		$groups = acf_get_field_groups(array('post_type' => $post_type));
		$group_fields = acf_get_fields($groups[0]['key']);
		$acf_list_id = array();
		foreach($group_fields as $acffield){
			//search first level
			$acf_list_id[$acffield['name']] = $acffield['key'];
			
			//search sub fields
			if(isset($acffield['sub_fields']) && count($acffield['sub_fields'])>0){
				foreach($acffield['sub_fields'] as $subfield){
						$acf_list_id[$subfield['name']] = $subfield['key'];
						
					//search sub sub fields
					if(isset($subfield['sub_fields']) && count($subfield['sub_fields'])>0){
						foreach($subfield['sub_fields'] as $sub_sub_field){
							$acf_list_id[$sub_sub_field['name']] = $sub_sub_field['key'];
						}
					}

				}
			}
		}
		return $acf_list_id;
	}
	
	private function export_users(){
		
		$users = get_users();
		$data = '';
		$header_fields_a = array(
			'ID',
			'user_login',
			'user_nicename',
			'user_email',
			'user_url',
			'user_registered',
			'user_status',
			'display_name',
			'roles'
		  );
		foreach($users as $user){
			/* GET USER DATA */
			//$user_data = get_userdata($user->ID);		
			/* ADD THIS LIST IN ONE LINE------------------------------------------- */						
			$line = '';
			foreach($header_fields_a as $element){
				
				/*if it's array implode it*/
				$value = $user->$element;
				if(is_array($user->$element)){
					$value = implode(',', $user->$element);
				}				
				$value = str_replace( '"' , '""' , $value );
				$value = '"' . $value . '"' . ";";
				$line .= $value;
			}
			
			/*ADD ACF----------------------------------------------------------------*/
			if( function_exists( 'get_fields' ) ) {
				$list_acf = $this->get_acf_keys($user->ID);
				foreach($list_acf as $acf_field_name=>$acf_field_key){
					//add to the title (putting key to avoid repetition)
					$header_fields_a[$acf_field_name] = $acf_field_name;				
					//get data
					$value = get_field($acf_field_key,$user->ID);
					if(!$value){
						$value = "";
					}
					if(is_array($value)){
						$value = implode(',', $value);
					}				
					$value = str_replace( '"' , '""' , $value );
					$value = '"' . $value . '"' . ";";
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
		$data = mb_convert_encoding($data,'ISO-8859-15','utf-8');
		print "$header\r\n$data";

		$this->results = true;
		return true;		
	}
	
	private function get_acf_keys($id){
		$groups = acf_get_field_groups(array('user_id' => $id));
		$acf_list_id = array();
		if(isset($groups[0]['key'])){
			
			foreach($groups as $group){
				$group_fields = acf_get_fields($group['key']);
				foreach($group_fields as $acffield){
					//search first level
					$acf_list_id[$acffield['name']] = $acffield['key'];

					//search sub fields
					if(isset($acffield['sub_fields']) && count($acffield['sub_fields'])>0){
						$acf_list_id = $this->get_sub_acf_fields($acf_list_id,$acffield['sub_fields']);
					}
				}
			}
			
			
		}
		return $acf_list_id;
	}

	private function get_sub_acf_fields($acf_list_id,$subfield){
		
		if(isset($array_search[$subfield['name']]) && $subfield['name'] == $array_search[$subfield['name']]){
			$acf_list_id[$subfield['name']] = $subfield['key'];
		}
		if(isset($subfield['sub_fields']) && count($subfield['sub_fields'])>0){
			$acf_list_id = $this->get_sub_acf_fields($acf_list_id,$subfield['sub_fields']);
		}
		
		return $acf_list_id;
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

			if($post_type == 'WP_users'){
				$result = $this->export_users();
				return $result;
			}
			
		}
		$data = '';
		
		$posts_args = array( 
		  'post_type'=>$post_type, 
		  'posts_per_page'=>-1, 
		  'post_status'=>'any', 
		  'lang'=>'' );
		//Filter pour les arguments
		$posts_args = apply_filters('wpc_export_args_get_posts_args',$posts_args,$_GET);
		
		$posts = get_posts( $posts_args );
		//Filter pour les resultat
		$posts = apply_filters('wpc_export_args_get_posts',$posts,$_GET);
		
		if( $posts ) {
			
			$count = count( $posts );
			
			if( function_exists( 'get_fields' ) ) {
				$acf_fields_a = get_fields( $posts[0]->ID );
			}
			
			// echo '<pre>ACF fields:<br/>'; var_dump( $fields ); echo '</pre>'; // DEBUG
			
			$header_fields_a = array(
				'"ID"',
				'"post_title"',
				'"URL"'
			);		
			if( !empty( $acf_fields_a ) && is_array( $acf_fields_a ) ) {
				//$header_fields_a = array_merge( $header_fields_a, array_keys( $acf_fields_a ) );
                foreach($acf_fields_a as $acf_fields_key=>$acf_fields){
                    $header_fields_a[] = '"'.$acf_fields_key.'"';
                }
			}
			$header_fields_a = apply_filters( 'wpc_export_header', $header_fields_a, $post_type );

			foreach( $posts as $post ) {
				
				$line = '';
				
				/** Post ID, post title and URL first **/
				$line .= $post->ID . ';';
				$value = str_replace( '"' , '""' , $post->post_title );
				$value = '"' . $value . '"' . ";";
				$line .= $value;
				if( $url = get_permalink( $post->ID ) ) {
					$value = '"' . $url . '"' . ";";
				} else {
					$value = '"N/A";';
				}
				$line .= $value;
				
				/** All ACF fields next **/
				if( function_exists( 'get_field' ) && !empty( $acf_fields_a ) && is_array( $acf_fields_a ) ) {
					foreach( array_keys( $acf_fields_a ) as $acf_field ) {
						$value = get_field( $acf_field, $post->ID );
						if ( ( !isset( $value ) ) || ( $value == "" ) ) {
							$value = ";";
						} elseif( is_object( $value ) ) {
							$value = $value->ID;
							//$value = '"*OBJECT*"' . ";";
						} elseif( is_array( $value ) ) {
							$value_s = '';
							foreach( $value as $k => $val ) {
								if( is_array( $val ) ) {
									foreach( $val as $key => $v ) {
										$key = str_replace( '"' , '""' , $key );
										
										if(is_object($v)){
											$v = $v->ID;
										}
										if(is_array($v) && isset($v['ID'])){
											$v = $v['ID'];
										}
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
				$line = apply_filters( 'wpc_export_line', $line, $post->ID, $post_type );
				$data .= trim( $line ) . "\r\n";
			}			
			$header = implode( ';', $header_fields_a );
			
			if( !empty( $_GET['debug'] ) && 1==$_GET['debug'] ) {
				echo '<h1>Export debug</h1>';
				echo '<p><strong>Found:</strong> ' . $count . '</p>';
			} else {
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=export.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
			}
            $data = mb_convert_encoding($data,'ISO-8859-15','utf-8');
			print "$header\r\n$data";
			
			$this->results = true;
			return true;
			
		} else {
			
			$this->results = false;
			return false;
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
