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
		
		/** args for the get users and get users **/
		$args = array();
		$args = apply_filters('wpc_user_args_query_filter',$args,$_GET);
		$users = get_users($args);		
		
		/** set fields **/
		$user_fields = array(
			'ID'=>'ID',
			'user_login'=>'user_login',
			'user_nicename'=>'user_nicename',
			'user_email'=>'user_email',
			'user_url'=>'user_url',
			'user_registered'=>'user_registered',
			'user_status'=>'user_status',
			'display_name'=>'display_name',
			'roles'=>'roles'
		);
		
		/** add ACF fields  **/ 
		$the_field_list_acf = array(); //keep a list of them seperated of the complete list
		if( function_exists( 'get_fields' ) ) {
			$acf_fields_array = get_fields( 'user_'.$users[0]->ID );
			if( !empty( $acf_fields_array ) && is_array( $acf_fields_array ) ) {
				//$header_fields_a = array_merge( $header_fields_a, array_keys( $acf_fields_a ) );
				foreach($acf_fields_array as $acf_fields_key=>$acf_fields){
					$the_field_list_acf[$acf_fields_key] = $acf_fields_key;
					//$order_fields[] = $acf_fields_key;
				}
			}			
		}
		
		/** add/remove other fields **/
		$the_field_list_acf = apply_filters( 'wpc_export_user_acffields', $the_field_list_acf,$_GET );
		
		$list_titles_buddypress = $this->get_user_data_buddypress($users[0]->ID,true);
		
		echo "<pre>", print_r("IN HERE --- ", 1), "</pre>";
		echo "<pre>", print_r($list_titles_buddypress, 1), "</pre>";
		die('STOP -- ');
		
		$list_titles_buddypress = apply_filters( 'wpc_export_user_buddypressfields', $list_titles_buddypress,$_GET );
		
		/** order fields **/
		$complete_list = array_merge($user_fields,$the_field_list_acf);
		$complete_list = array_merge($complete_list,$list_titles_buddypress);
		$order_fields = apply_filters( 'wpc_export_user_order', $complete_list,$_GET );
		
		/** headers (for titles) **/
		$header_fields = apply_filters('wpc_user_header_fields_filter',$order_fields,$_GET);
				
		/** change list of users **/
		$users = apply_filters('wpc_users_result_filter',$users,$_GET);
		$data = '';
		foreach($users as $user){
			
			/* ADD THIS LIST IN ONE LINE------------------------------------------- */						
			$line = array();
			foreach($order_fields as $element){
				/*if it's array implode it*/
				$value = $user->$element;
				if(is_array($user->$element)){
					$value = implode(',', $user->$element);
				}				
				$value = str_replace( '"' , '""' , $value );
				$value = '"' . $value . '"' . ";";
				$line[] = $value;
			}
			
			/*ADD ACF----------------------------------------------------------------*/
			if( function_exists( 'get_fields' ) ) {
				foreach($the_field_list_acf as $acf_field_name=>$acf_field_key){
					//get data
					$value = get_field('user_'.$acf_field_key,$user->ID);
					if(!$value){
						$value = "";
					}
					if(is_array($value)){
						$value = implode(',', $value);
					}				
					$value = str_replace( '"' , '""' , $value );
					$value = '"' . $value . '"' . ";";
					$line[$acf_field_key] = $value;
				}
			}
			
			/*ADD buddypress ----------------------------------------------------------------*/
			$list_values_buddypress = $this->get_user_data_buddypress($users[0]->ID);
			foreach($list_values_buddypress as $buddy_value){
				$value = str_replace( '"' , '""' , $buddy_value );
				$value = '"' . $value . '"' . ";";
				$line[] = $value;
			}
			
			/** add/remove fields **/
			$line = apply_filters( 'wpc_export_user_line', $line, $user->ID, $_GET );
			$data .= trim( implode('',$line) ) . "\r\n";
		}
		
		$header = implode( ';', $header_fields );
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
		
		if(isset($subfield[0]['name'])){
			$subfield = $subfield[0];
		}
		
		if(
			isset($array_search[$subfield['name']]) 
			&& isset($subfield['name']) 
			&& $subfield['name'] == $array_search[$subfield['name']]){
			$acf_list_id[$subfield['name']] = $subfield['key'];
		}
		if(isset($subfield['sub_fields']) && count($subfield['sub_fields'])>0){
			$acf_list_id = $this->get_sub_acf_fields($acf_list_id,$subfield['sub_fields']);
		}
		
		return $acf_list_id;
	}
	
	private function get_user_data_buddypress($user_id = null,$titles = false, $args = array()){
		// Bail if no user ID.
		if ( empty( $user_id ) ) {
			echo "<pre>", print_r("1 -- ", 1), "</pre>";
			return array();
		}
		if (!function_exists("bp_parse_args") ) {
			echo "<pre>", print_r("2 -- ", 1), "</pre>";
			return array();
		}		

		if(!isset($args['args'])){
			$args['args'] = array();
		}
		$r = bp_parse_args( $args['args'], array(
			'profile_group_id' => 0,
			'user_id'          => $user_id
		), 'bp_xprofile_user_admin_profile_loop_args' );

		// We really need these args.
//		if ( empty( $r['profile_group_id'] ) || empty( $r['user_id'] ) ) {
//			return array();
//		}
//
//		// Bail if no profile fields are available.
//		if ( ! bp_has_profile( $r ) ) {
//			return array();
//		}

		$list_to_return = array();
		
echo "<pre>", print_r("r", 1), "</pre>";
echo "<pre>", print_r($r, 1), "</pre>";		
echo "<pre>", print_r("bp_the_profile_group()", 1), "</pre>";
echo "<pre>", print_r(bp_the_profile_group(), 1), "</pre>";
		
		// Loop through profile groups & fields.
		while ( bp_profile_groups() ) : bp_the_profile_group();

			//group info name echo  bp_get_the_profile_group_slug()
		
echo "<pre>", print_r("TEST  - ", 1), "</pre>";
echo "<pre>", print_r( bp_get_the_profile_group_slug(), 1), "</pre>";

			if ( bp_get_the_profile_group_description() ) {
				//group info description bp_the_profile_group_description();
			}
			
			while ( bp_profile_fields() ) : bp_the_profile_field();

echo "<pre>", print_r("bp_get_the_profile_field_input_name()", 1), "</pre>";
echo "<pre>", print_r(bp_get_the_profile_field_input_name(), 1), "</pre>";
			
					//field name
					if($titles){
						$list_to_return[] =  bp_get_the_profile_field_input_name();
					}else{
						$list_to_return[] =  bp_get_the_profile_field_edit_value();
					}

			endwhile; // End bp_profile_fields()

		endwhile; // End bp_profile_groups.
		
		return $list_to_return;
die("-----------------");
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
		$posts_args = apply_filters('wpc_export_args_get_posts_args',$posts_args,$_GET);
		
		$posts = get_posts( $posts_args );
		$posts = apply_filters('wpc_export_args_get_posts',$posts,$_GET);
		
		if( $posts ) {
			
			$count = count( $posts );
			
			$complete_list_columns = array();
			
			if( function_exists( 'get_fields' ) ) {
				$acf_fields_a = get_fields( $posts[0]->ID );
			}			
			// echo '<pre>ACF fields:<br/>'; var_dump( $fields ); echo '</pre>'; // DEBUG
			
			$the_field_list_post = array(
				'ID',
				'post_title',
				'URL'
			);
			$order_fields = $the_field_list_post; //will be completed by acf list if needed
			
			$the_field_list_acf = array();
			if( !empty( $acf_fields_a ) && is_array( $acf_fields_a ) ) {
				//$header_fields_a = array_merge( $header_fields_a, array_keys( $acf_fields_a ) );
                foreach($acf_fields_a as $acf_fields_key=>$acf_fields){
					$the_field_list_acf[] = $acf_fields_key;
                    $order_fields[] = $acf_fields_key;
                }
			}
			
			/** add remove fields linked to the post **/
			$the_field_list_post = apply_filters( 'wpc_export_fields_post', $the_field_list_post, $post_type );
			
			/** add remove fields linked to the ACF **/
			$the_field_list_acf = apply_filters( 'wpc_export_fields_post_acf', $the_field_list_acf, $post_type );
			
			/** we have all fields, now needs the order, or add/remove field **/
			$order_fields = apply_filters( 'wpc_export_order', $order_fields, $post_type );
	
			/** now with the order create the titles **/
			$header_fields_a = array();
			foreach($order_fields as $thefield_header){
				$header_fields_a[$thefield_header] = '"'.$thefield_header.'"';
			}
			/** can now replace titles **/
			$header_fields_a = apply_filters( 'wpc_export_header', $header_fields_a, $post_type );

			foreach( $posts as $post ) {				
				$line = array();
				/** Post ID, post title and URL first **/
				foreach($the_field_list_post as $thefield_key_index => $thefield_key){
					if(isset($post->$thefield_key) || $thefield_key=="URL"){						
						/** standard value **/
						$value = $post->$thefield_key . ';';
						/** for special value **/
						if($thefield_key == "post_title"){
							$value = str_replace( '"' , '""' , $post->post_title ).';';
						}
						if($thefield_key == "URL"){
							if( $url = get_permalink( $post->ID ) ) {
								$value = '"' . $url . '"' . ";";
							} else {
								$value = '"N/A";';
							}
						}
						
						$line[$thefield_key] = $value;
					}
				}
				
				/** All ACF fields next **/				
				if( function_exists( 'get_field' ) && !empty( $the_field_list_acf ) && is_array( $the_field_list_acf ) ) {
					//foreach( array_keys( $acf_fields_a ) as $acf_field ) {
					foreach( $the_field_list_acf as $acf_field ) {						
						$value = get_field( $acf_field, $post->ID );
						if ( ( !isset( $value ) ) || ( $value == "" ) ) {
							$value = ";";
						} elseif( is_object( $value ) ) {
							$value = $value->ID.";";
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
						//$line .= $value;
						$line[$acf_field] = $value;						
					}
				}
				$line = apply_filters( 'wpc_export_line', $line, $post->ID, $post_type );
				
				/** reorder line **/
				$new_line_ordered = array();
				foreach($order_fields as $the_field_order){
					if(isset($line[$the_field_order])){
						$new_line_ordered[] = $line[$the_field_order];
					}else{
						$new_line_ordered[] = '"";';
					}
				}
				
				$data .= trim( implode('',$new_line_ordered) ) . "\r\n";
			}			
			
			/** reorder titles **/			
			$new_titles_ordered = array();
			foreach($order_fields as $the_field_order){
				if(isset($header_fields_a[$the_field_order])){
					$new_titles_ordered[] = $header_fields_a[$the_field_order];
				}else{
					$new_titles_ordered[] = '';
				}
			}		
			$header = implode( ';', $new_titles_ordered );
			
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
