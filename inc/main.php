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
		
		if( !empty( $_GET['action'] ) && 'Exporter par date' == $_GET['action'] ) {
			$this->export();
		}
		
		//TODO: select post type
		?>
		<h2>Export WordPress posts and fields to CSV</h2>
		<form>
		<input type="submit" name="action" value="Export" class="wpexportpfcsv" />
		<input type="hidden" name="page" value="<?php echo htmlentities($_GET['page']); ?>" />
		</form>
		<?php
	}
	
	/**
	 * Generation of export dump
	 * 
	 */
	private function export() {

		if( $posts = get_posts( array( 'post_type'=>'centre', 'posts_per_page'=>1 ) ) ) {
			$fields = get_fields( $posts[0]->ID );
		}
		echo '<pre>ACF fields:<br/>'; var_dump( $fields ); echo '</pre>';
	}
}
?>