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
	public $_posttype_button_toadd_key = "posttype_button_toadd";
	public $_nbr_list_filters_key = "nbr_list_filters_key";
	public $_list_filters_key = "list_filters_key";
	
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
		
		if(is_admin()){
			add_action( 'wp_ajax_add_button_admin_download_csv',  array( $this, 'add_button_admin_download_csv'));
			add_action( 'admin_init', array( $this,'wpexport_scripts')); 
		}
	}
	
	public function wpexport_scripts(){
		wp_enqueue_script(
			'wpexportadminscripts', 
			plugin_dir_url( dirname(__FILE__) ).'js/admin_scripts.js' , 
			array('jquery'), '0.0.1', false);

		$list = array();
		$options_buttons_list = get_option($this->_list_filters_key);
		if($options_buttons_list):
			$list = json_decode($options_buttons_list,ARRAY_A);
		endif;
		wp_localize_script('wpexportadminscripts', 'list_filters_export', $list );

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
			
			<hr>
			
		<?php	
		//SAVE PART
		//if posttype_button_toadd_form is set we 
		if(isset($_POST['posttype_button_toadd_form'])):
			
			$post_type_to_add = array();
			if(isset($_POST['posttype_button_toadd'])):
				$post_type_to_add = $_POST['posttype_button_toadd'];
			endif;
			update_option( $this->_posttype_button_toadd_key, json_encode($post_type_to_add));
			
			$nbr_filter_wpexport_toadd = 0;
			if(isset( $_POST['nbr_filter_wpexport']) &&  $_POST['nbr_filter_wpexport']!=''):
				$nbr_filter_wpexport_toadd = $_POST['nbr_filter_wpexport'];
			endif;
			update_option( $this->_nbr_list_filters_key,$nbr_filter_wpexport_toadd);
			
			$list_filter_to_save = array();
			foreach($_POST as $key_post=>$value_post):
				if(preg_match('#filter_wpexport_#',$key_post)):
					$list_filter_to_save[] = $value_post;
				endif;
			endforeach;
			update_option( $this->_list_filters_key, json_encode($list_filter_to_save,JSON_FORCE_OBJECT));
		endif;		
		//END SAVE PART
				
		$options_buttons_list = get_option($this->_posttype_button_toadd_key);
		if($options_buttons_list):
			$options_buttons_list = json_decode($options_buttons_list,ARRAY_A);
		endif;
		?>
		<form method="post">
			<h3><?php _e('Add export button to post type', 'wpexportpfcsv'); ?></h3>			
				<ul>
					<?php foreach( $post_types as $post_type ) :
						$selected = "";
						if($options_buttons_list && in_array($post_type, $options_buttons_list)):
							$selected = "checked";
						endif;
						?>
					<li>
						<input name="posttype_button_toadd[]" <?php echo $selected; ?> 
							   type="checkbox" value="<?php echo $post_type; ?>">&nbsp;<?php echo $post_type; ?>
					</li>
					<?php endforeach; ?>
					<?php 
					$selected = "";
					if($options_buttons_list && in_array('WP_users', $options_buttons_list)): 
						$selected = "checked";
					endif;
					?>
					<li>
						<input name="posttype_button_toadd[]" <?php echo $selected; ?> 
							   type="checkbox" value="WP_users">&nbsp;WP_users
					</li>
					
				</ul>		
			
			<!-- FILTERS TO CHECK OUT -->
			<h3>List filtres</h3>		
			<div>
				<span><?php _e('Nombre de filtres : ','wpexportpfcsv'); ?> </span>
				<?php
				$nbr_list_filters = get_option($this->_nbr_list_filters_key);
				if(!$nbr_list_filters)
					$nbr_list_filters = 0;				
				?>
				<input type="text" name="nbr_filter_wpexport" value="<?php echo $nbr_list_filters; ?>">
			</div>			
			<ul>
			<?php 
				$list_filters = get_option($this->_list_filters_key);
				if($list_filters):
					$list_filters = json_decode($list_filters,ARRAY_A);
				endif;				
								
				for($alpha=0;$alpha<$nbr_list_filters;$alpha++):
					$value_filter = '';
					if(isset($list_filters[$alpha])):
						$value_filter = $list_filters[$alpha];
					endif;
			?>
				<li>
					<input type="text" 
						   name="filter_wpexport_<?php echo $alpha; ?>" 
						   value="<?php echo $value_filter; ?>"
						   style="width: 280px;">
				</li>
				
			<?php endfor;?>				
			</ul>
			
			<input type="submit" name="action" value="<?php _e( 'Save options', 'wpexportpfcsv' ); ?>" class="wpexportpfcsv" />
			<input type="hidden" name="posttype_button_toadd_form" value="1">
		</form>		
		</div>
		<?php
	}
	
	public function add_button_admin_download_csv(){
		$link = false;
		//if post_type and no post
		$post_type = $_POST['post_type'];
		
		$options_buttons_list = get_option($this->_posttype_button_toadd_key);
		if($options_buttons_list):
			$options_buttons_list = json_decode($options_buttons_list,ARRAY_A);
		endif;
		if($options_buttons_list && in_array($post_type, $options_buttons_list)){
			//EXport normal
			$link_url = home_url().'/wp-admin/admin.php?page=wpexportpfcsv&post_type='.$post_type.'&action=Export';
			$link = '<a href="'.$link_url.'" class="ac-button add-new-h2 ac-button-toggle-edit" style="top: 7px !important;">'.__('Export CSV','wpexportpfcsv').'</a>';
			
			//verions filtré
			$add_to_link = "";
			if(isset($_POST['data_filters'])){
				foreach($_POST['data_filters'] as $postkey=>$postvalue){
					$add_to_link.= "&".$postkey."=".$postvalue;
				}
			}
						
			$link_url = home_url().'/wp-admin/admin.php?page=wpexportpfcsv&post_type='.$post_type.'&action=Export'.$add_to_link;
			$link_url = apply_filters('wpexport_change_link_filter_admin',$link_url,$post_type);
			
			$link.= '<a href="'.$link_url.'" class="ac-button add-new-h2 ac-button-toggle-edit" style="top: 7px !important;">'.__('Export CSV filtré','wpexportpfcsv').'</a>';		
		}	
		echo $link;
		wp_die();
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
		
		if(count($users)==0){
			echo 'no users';
			return true;
		}
		
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
		
		/** add buddypress titles **/
		$list_titles_buddypress = $this->get_user_data_buddypress($users[0]->ID,true);		
		$list_titles_buddypress = apply_filters( 'wpc_export_user_buddypressfields', $list_titles_buddypress,$_GET );
		
		/** order fields **/		
		//$complete_list = array_merge($user_fields,$the_field_list_acf);
		//$complete_list = array_merge($complete_list,$list_titles_buddypress);
		$order_fields = apply_filters( 'wpc_export_user_order', $user_fields,$_GET );
				
		/** headers (for titles) **/
		$header_fields = array_merge($order_fields,$the_field_list_acf);
		$header_fields = array_merge($header_fields,$list_titles_buddypress);
		$header_fields = apply_filters('wpc_user_header_fields_filter',$header_fields,$_GET);	
		
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
			if( function_exists( 'get_field' ) ) {
				foreach($the_field_list_acf as $acf_field_name=>$acf_field_key){
					
					$value = get_field($acf_field_key,'user_'.$user->ID);
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
			$list_values_buddypress = $this->get_user_data_buddypress($user->ID);
			foreach($list_values_buddypress as $buddy_value){				
				$buddy_value = str_replace( '"' , '""' , $buddy_value );
				$buddy_value = '"' . $buddy_value . '"' . ";";
				$line[] = $buddy_value;	
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
	
	private function get_user_data_buddypress($user_id = null,$titles = false){
		// Bail if no user ID.
		if ( empty( $user_id ) ) {
			return array();
		}
		//Bail if the buddypress doesn't exists
		if (!function_exists("bp_has_profile") ) {
			return array();
		}		
		
		/** need to init profile to the loop to work **/
		$profile_args = array('user_id' => $user_id);
		$profile = bp_has_profile( $profile_args );

		$list_to_return = array();				
		// Loop through profile groups & fields.
		while ( bp_profile_groups() ) : bp_the_profile_group();

			//group info name echo  bp_get_the_profile_group_slug()
//			if ( bp_get_the_profile_group_description() ) {
//				//group info description bp_the_profile_group_description();
//			}
			
			while ( bp_profile_fields() ) : bp_the_profile_field();			
					//field name
					if($titles){
						$list_to_return[] = bp_get_the_profile_field_name();
					}else{
						$list_to_return[] =  bp_get_the_profile_field_edit_value();
					}

			endwhile; // End bp_profile_fields()

		endwhile; // End bp_profile_groups.
		
		return $list_to_return;
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
										
										//ok so $v can be an array of object
										$temp_ids = array();						
										if(is_array($v) && count($v)>0){				
											foreach($v as $element_value){												
												if(is_object($element_value)){
													if(isset($element_value->ID) && $element_value->ID!=""){
														$temp_ids[] = $element_value->ID;
													}elseif(isset($element_value->term_id) && $element_value->term_id!=""){
														$temp_ids[] = $element_value->term_id;
													}
												}
												if(is_array($element_value)){
													if(isset($element_value['ID']) && $element_value['ID']!=""){
														$temp_ids[] = $element_value['ID'];
													}elseif(isset($element_value['term_id']) && $element_value['term_id']!=""){
														$temp_ids[] = $element_value['term_id'];
													}
												}
											}											
											$v = implode('|', $temp_ids);
										}
										
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
				
				$new_line_ordered = apply_filters( 'wpc_export_new_line_ordered', $new_line_ordered, $post->ID, $post_type );
				if(!empty($line)){
					$data .= trim( implode('',$new_line_ordered) ) . "\r\n";
				}
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
			$header = mb_convert_encoding($header, "ISO-8859-15", mb_detect_encoding($header, "UTF-8, ISO-8859-1, ISO-8859-15", true));
			
			if( !empty( $_GET['debug'] ) && 1==$_GET['debug'] ) {
				echo '<h1>Export debug</h1>';
				echo '<p><strong>Found:</strong> ' . $count . '</p>';
			} else {
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=export.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
			}
            //$data = mb_convert_encoding($data,'ISO-8859-15','utf-8');
			$data = mb_convert_encoding($data, "ISO-8859-15", mb_detect_encoding($data, "UTF-8, ISO-8859-1, ISO-8859-15", true));
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