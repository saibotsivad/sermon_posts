<?php
class tl_sermon_posts_admin extends tl_sermon_posts_core
{
	var $table_names = array(
		'bible' => 'tlsp_bible',
		'books' => 'tlsp_book',
		'refs'  => 'tlsp_reference'
	);
	function __construct()
	{
		// need to call the parent class for functionality
		parent::__construct();
		
		// activation/deactivation
		register_activation_hook( $this->plugin_info['plugin_location'], array( $this, 'plugin_activation' ) );
		register_deactivation_hook( $this->plugin_info['plugin_location'], array( $this, 'plugin_deactivation' ) );
		
		// admin actions
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		
		// AJAX callbacks
		add_action('wp_ajax_tlsp_save_reference', array( $this, 'ajax_save_reference' ) );
		add_action('wp_ajax_tlsp_add_new_reference', array( $this, 'ajax_add_new_reference' ) );
		add_action('wp_ajax_tlsp_sermon_reference_save', array( $this, 'ajax_sermon_reference_save' ) );
		add_action('wp_ajax_tlsp_sermon_reference_delete', array( $this, 'ajax_sermon_reference_delete' ) );
	}
	
	/**
	 * Register an importer method, a metabox, a custom file upload window, and some settings.
	 *
	 * @since 0.06
	 * @author saibotsivad
	*/
	function admin_init()
	{
		// register styling
		wp_register_style( 'metabox-styling', plugins_url( 'metabox.css', __FILE__ ) );
		wp_enqueue_style( 'metabox-styling' );
		
		// register an import method
		register_importer(
			'tlsp_importer',
			'Sermon Import',
			'Import sermons from the Sermon Browser plugin to the Sermon Posts plugin.',
			array( $this, 'Importing' )
		);
	
	}
	
	/**
	 * Given a verse in JSon form, it verifies that it is correct and returns true or false.
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function ajax_save_reference()
	{
		// all fields required
		try {
			$post_id = (int) $_POST['post_id'];
			$reference_id = (int) $_POST['reference_id'];
			$from_book = (int) $_POST['from_book'];
			$from_chapter = (int) $_POST['from_chapter'];
			$from_verse = (int) $_POST['from_verse'];
			$through_book = (int) $_POST['through_book'];
			$through_chapter = (int) $_POST['through_chapter'];
			$through_verse = (int) $_POST['through_verse'];
		}
		catch (Exception $e)
		{
			echo json_encode('fail');
			die();
		}
		
		// generate new verse array
		$verse = tlsp_generate_verse_range( $from_book, $from_chapter, $from_verse, $through_book, $through_chapter, $through_verse );
		$verse['post_id'] = $post_id;
		$verse['reference_id'] = ( empty( $reference_id ) || $reference_id == 0 ? null : $reference_id );
		
		// save and exit
		$verse = tlsp_admin::save_sermon_verse_range( $verse );
		echo json_encode($verse);
		die();
	}
	
	/**
	 * AJAX: Remove a given verse id from a post.
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function ajax_sermon_reference_delete()
	{
		// we require all these fields
		try {
			$verse_id = (int) $_POST['verse_id'];
			$post_id  = (int) $_POST['post_id'];
			
			$verse_from    = (int) $_POST['verse_from'];
			$verse_through = (int) $_POST['verse_through'];
		}
		catch (Exception $e)
		{
			echo json_encode('fail');
			die();
		}
		
		// remove the verse
		$sermon_verses = get_post_meta( $post_id, 'tlsp_sermon_reference', true );
		if ( isset( $sermon_verses[$verse_id] ) )
		{
			// remove from post_meta
			unset( $sermon_verses[$verse_id] );
			update_post_meta( $post_id, 'tlsp_sermon_reference', $sermon_verses );
			
			// remove from database
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM wp_tlsp_reference WHERE wp_tlsp_reference.sermon = %d AND wp_tlsp_reference.start = %d AND wp_tlsp_reference.end = %d",
				$post_id, $verse_from, $verse_through ) );
			
			// output success
			echo json_encode(array(
				'status'   => 'success',
				'verse_id' => $verse_id
			));
		}
		else
		{
			echo json_encode('fail');
		}
		die();
	}
	
	/**
	 * The AJAX passage reference saving mechanism.
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function ajax_sermon_reference_save()
	{
		// we require all of these fields
		try {
			$verse_id = (int) $_POST['verse_id'];
			$post_id  = (int) $_POST['post_id'];
			
			$from_book       = (int) $_POST['from_book'];
			$from_chapter    = (int) $_POST['from_chapter'];
			$from_verse      = (int) $_POST['from_verse'];
			$through_book    = (int) $_POST['through_book'];
			$through_chapter = (int) $_POST['through_chapter'];
			$through_verse   = (int) $_POST['through_verse'];
		}
		catch (Exception $e)
		{
			echo json_encode('fail');
			die();
		}
		
		// generate the verse information
		$verse_range = tlsp_generate_verse_range( $from_book, $from_chapter, $from_verse, $through_book, $through_chapter, $through_verse );
		
		// add the verse range to the post_meta, overwriting the old one (if it exists)
		$sermon_verses = get_post_meta( $post_id, 'tlsp_sermon_reference', true );
		$sermon_verses = ( is_array( $sermon_verses ) ? $sermon_verses : array() );
		$sermon_verses[$verse_id] = $verse_range;
		update_post_meta( $post_id, 'tlsp_sermon_reference', $sermon_verses );
		
		// add the verse range to the sermon_posts table
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM wp_tlsp_reference WHERE wp_tlsp_reference.sermon = %d AND wp_tlsp_reference.start = %d AND wp_tlsp_reference.end = %d",
			$post_id, $verse_range['from_id'], $verse_range['through_id'] ) );
		
		// finally, generate the output
		$output = get_post_meta( $post_id, 'tlsp_sermon_reference', true );
		$output = $output[$verse_id];
		$output['verse_id'] = $verse_id;
		echo json_encode($output);
		die();
	}
	
	/**
	 * Add metabox and other admin styles and scripts.
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function enqueue_scripts()
	{
		wp_register_style( 'metabox-styling', plugins_url( 'metabox.css', __FILE__ ) );
		wp_enqueue_style( 'metabox-styling' );
	}
	
	/**
	 * On activation, some errors are fatal, but we want to tell the user why.
	 *
	 * @since 0.16
	 * @author saibotsivad
	*/
	function fatal_error_message( $message, $error_num )
	{
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'error_scrape' )
		{
			echo '<strong>' . $message . '</strong>';
			exit;
		}
		else
		{
			trigger_error( $message, $error_num );
		}
	}
	
	/**
	 * Installation of tables, these hold the verse ranges for easier querying.
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function install()
	{
		require_once( dirname(__FILE__) . '/install_script.php' );
	}
	
	/**
	 * Menu page: "Sermons > Manage Files"
	 * Note: Most of this wouldn't be necessary if the file manager had the appropriate hooks.
	 *
	 * @since 0.03
	 * @author saibotsivad
	*/
	function manage_files()
	{
	
		// when the user clicks on "Edit", it goes back to this page, passing in this variable
		if ( isset( $_GET[ 'editfileid' ] ) )
		{
			include( dirname(__FILE__) . '/file_edit.php' );
		}
		
		// when the user wants to delete files, it goes to this page to verify deleting
		elseif ( isset( $_GET['trashfile'] ) || ( isset( $_GET['bulk_action'] ) && $_GET['bulk_action'] == 'trash' ) )
		{
			include( dirname(__FILE__) . '/file_delete.php' );
		}
		
		// otherwise, display the custom file manager
		else
		{
			include( dirname(__FILE__) . '/file_manager.php' );
		}
	
	}
	
	/**
	 * Manage the taxonomies with a custom interface which is a little easier to use.
	 *
	 * @since 0.05
	 * @author saibotsivad
	*/
	function manage_taxonomy()
	{
		include( dirname(__FILE__) . '/taxonomy.php' );
	}
	
	/**
	 * Register the "Settings > Sermon Posts", "Sermons > Manage Files" menus, and replacing the default taxonomy
	 * menus with custom ones so they can be managed easier.
	 *
	 * @since 0.05
	 * @author saibotsivad
	*/
	function menu()
	{
	
		// plugin settings page
		add_options_page(
			'Sermon Posts Options',
			'Sermon Posts',
			'manage_options',
			'tl_sermonposts_settings',
			array( $this, 'options_page' )
		);
		
		// custom taxonomy management pages
		add_submenu_page(
			'edit.php?post_type=sermon_post',
			'Services',
			'Services',
			'manage_options',
			'tl_sermons-tlsp_service',
			array( $this, 'manage_taxonomy' )
		);
		
		add_submenu_page(
			'edit.php?post_type=sermon_post',
			'Series',
			'Series',
			'manage_options',
			'tl_sermons-tlsp_series',
			array( $this, 'manage_taxonomy' )
		);
		
		add_submenu_page(
			'edit.php?post_type=sermon_post',
			'Preachers',
			'Preachers',
			'manage_options',
			'tl_sermons-tlsp_preacher',
			array( $this, 'manage_taxonomy' )
		);
		
		// file manager
		add_submenu_page(
			'edit.php?post_type=sermon_post',
			'Manage Files',
			'Manage Files',
			'edit_posts',
			'tl_sermons-files',
			array( $this, 'manage_files' )
		);
	
	}
	
	/**
	 * Manage the sermon details (passages, speaker, etc.)
	 *
	 * @since 0.01
	 * @author saibotsivad
	*/
	function metabox_add_sermon()
	{
		include( 'metabox_add_sermon.php' );
	}
	
	/**
	 * Manage the sermon files and file details
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function metabox_files()
	{
		include( 'metabox_files.php' );
	}
	
	/**
	 * Register and remove the sermon post metaboxes
	 *
	 * @since 0.17
	 * @author saibotsivad
	*/
	function metabox_setup()
	{
	
		// the metabox for passages, preachers, etc.
		add_meta_box(
			'tlsp_metabox_add_sermon',
			'Sermon Details',
			array( $this, 'metabox_add_sermon' ),
			'sermon_post',
			'normal',
			'high'
		);
		
		// the metabox for handling files
		add_meta_box(
			'tlsp_metabox_files',
			'Sermon Files',
			array( $this, 'metabox_files' ),
			'sermon_post',
			'side',
			'core'
		);
		
		// both of these are handled in the "Sermon Information" metabox
		remove_meta_box( 'postcustom', 'sermon_post', 'normal' );
		remove_meta_box( 'postimagediv', 'sermon_post', 'side' );
	
	}
	
	/**
	 * Add the Bible as two database tables, and populate with data.
	 * Create additional table to relate sermons to verse ranges (one to many relationship).
	 *
	 * @since 0.05
	 * @author saibotsivad
	*/
	function plugin_activation()
	{
		// so far I've been having serious trouble getting the Sermon Browser plugin to cooperate...
		if ( is_plugin_active( 'sermon-browser/sermon.php' ) )
		{
			$this->fatal_error_message( 'The Sermon Browser plugin needs to be deactivated first, unfortunately. For more information, see the Sermon Posts plugin page.', E_USER_ERROR );
		}
		
		// adding the tables to the database, etc., is done here
		$this->install();
		
		// plugin option data
		$options = array(
			'version' => '0.17',
			'options' => array(
				'manage-files-count' => array(
					'val' => 16,
					'type' => 'int', // int/text/tax
					'name' => 'Files Per Page',
					'desc' => 'The number of files to display per page, on the "Manage Files" page.'
				),
				'tlsp_preacher' => array(
					'val' => 0,
					'type' => 'tax',
					'name' => 'Default Preacher',
					'desc' => 'Set the default sermon preacher.'
				),
				'tlsp_series' => array(
					'val' => 0,
					'type' => 'tax',
					'name' => 'Default Series',
					'desc' => 'Set the default sermon series.'
				),
				'tlsp_service' => array(
					'val' => 0,
					'type' => 'tax',
					'name' => 'Default Service',
					'desc' => 'Set the default sermon service.'
				)
			)
		);
		update_option( 'plugin_tlsp_options', $options );
		
		// Rewrite rules are created
		$this->post_rewrite( true );
		
	}
	
	/**
	 * @since 0.01
	 * @author saibotsivad
	*/
	function plugin_deactivation()
	{
		// remove all rewrite rules, to ensure clean links throughout
		flush_rewrite_rules();
	}
	
	/**
	 * Save sermon information: Set taxonomies, set verse ranges
	 *
	 * @since 0.01
	 * @author saibotsivad
	*/
	function save_post()
	{
		// only run on the sermon_post save
		if ( isset( $_POST['tlsp_metabox_save'] ) )
		{
		
			global $post;
			
			// verify nonce, check user permissions
			if ( wp_verify_nonce( $_REQUEST['tlsp_metabox_save'], 'tlsp_metabox_save' ) && current_user_can( 'edit_posts', $post->ID ) )
			{

				// Description and date are handled magically by Wordpress if the element name and id are the same as the default field
				// Sermon tags are handled automagically as well
				
				// Save the taxonomy: preacher
				if ( isset( $_POST['tlsp_preacher'] ) )
				{
					wp_set_object_terms( $post->ID, (int)$_POST['tlsp_preacher'], 'tlsp_preacher', false );
				}
				
				// Save the taxonomy: series
				if ( isset( $_POST['tlsp_series'] ) )
				{
					wp_set_object_terms( $post->ID, (int)$_POST['tlsp_series'], 'tlsp_series', false );
				}
				
				// Save the taxonomy: service
				if ( isset( $_POST['tlsp_service'] ) )
				{
					wp_set_object_terms( $post->ID, (int)$_POST['tlsp_service'], 'tlsp_service', false );
				}

				// On saving, the list of verses, which has been pulled from the sermon_posts
				// database, completely overwrites whatever is in the postmeta table
				if ( isset( $_POST['tlsp_verse_ranges'] ) )
				{
					foreach( $_POST['tlsp_verse_ranges'] as $key => $value )
					{
						
					}
				}
			}
		
		}
	
	}

}