<?php
/*
Plugin Name: Sermon Posts
Plugin URI: http://tobiaslabs.com/sermonposts/
Description: A plugin for WordPress to manage your sermons with ease! View and edit MP3 files and MP3 tags, including the elusive <a href="http://en.wikipedia.org/wiki/ID3#ID3v2_Embedded_Image_Extension">albumart</a>. Uses built-in WordPress functionality, so styling is <a href="http://codex.wordpress.org/Template_Hierarchy">a snap</a>!
Author: saibotsivad
Version: 0.17
Author URI: http://davistobias.com
License: WTFPL
*/

// This plugin requires WordPress version 3.0.0 to run
if ( version_compare( get_bloginfo( 'version' ), '3.0.0' ) < 0 )
{
	wp_die( __( "Your version of WordPress is too old! You need version 3.0.0 or higher to install this plugin." ) );
}

// Core functions are held here
require_once( 'plugin/core_functions.php' );

// Initialization loads as little as possible
if ( is_admin() )
{
	require_once( 'plugin/admin_core.php' );
	require_once( 'plugin/admin_functions.php' );
	$tl_sermon_posts = new tl_sermon_posts_admin;
}
else
{
	$tl_sermon_posts = new tl_sermon_posts_core;
}

// The core visitor-side functionality is held held here
class tl_sermon_posts_core
{

	// plugin information, for the admin side
	var $plugin_info = array();
	
	/**
	 * @since 0.1
	 * @author saibotsivad
	*/
	function __construct()
	{
		// set the plugin information, for activation and other options
		$this->plugin_info['plugin_location'] = __FILE__;
		
		// Initialization
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'post_type_link', array( $this, 'post_permalink' ), 10, 3 );
		add_action( 'after_setup_theme', array( $this, 'enable_post_thumbnails' ), 9999 );
	}
	
	/**
	 * Enable post-thumbnail for sermon_post and attachments, even if the theme does not support it.
	 * Version 3.1 of WordPress changes the way post thumbnail support is added.
	 *
	 * @since 0.7
	 * @author saibotsivad
	*/
	function enable_post_thumbnails()
	{
	
		if ( get_bloginfo( 'version' ) >= '3.1' )
		{
			// make sure thumbnails work for sermon_post post type
			$thumbnails = get_theme_support( 'post-thumbnails' );
			if ( $thumbnails === false ) $thumbnails = array ();
			if ( is_array( $thumbnails ) )
			{
				$thumbnails[] = 'sermon_post';
				$thumbnails[] = 'post';
				$thumbnails[] = 'attachment';
				add_theme_support( 'post-thumbnails', $thumbnails );
			}
		}
		else
		{
			// required global variable
			global $_wp_theme_features;
			
			// if the post thumbnails are not set, we'll enable support to sermon_post posts
			if( !isset( $_wp_theme_features['post-thumbnails'] ) )
			{
				$_wp_theme_features['post-thumbnails'] = array( array( 'sermon_post', 'post' ) );
			}
			// if they are set, we'll add sermon_post posts to the array
			elseif ( is_array( $_wp_theme_features['post-thumbnails'] ) )
			{
				$_wp_theme_features['post-thumbnails'][0][] = 'sermon_post';
			}
		}
	
	}
	
	/**
	 * Register custom post type (sermon) and taxonomies (preacher, service, series, sermon tag)
	 *
	 * @since 0.1
	 * @author saibotsivad
	*/
	function init()
	{
	
		// register the new post type: sermon_post
		$args = array(
			'labels' => array(
				'name' => _x( 'Sermons', 'post type general name' ),
				'singular_name' => _x( 'Sermon', 'post type singular name') ,
				'add_new' => _x( 'Add New', 'sermon_post' ),
				'add_new_item' => __( 'Add New Sermon' ),
				'edit_item' => __( 'Edit Sermon' ),
				'new_item' => __( 'New Sermon' ),
				'view_item' => __( 'View Sermon' ),
				'search_items' => __( 'Search Sermons' ),
				'not_found' =>  __( 'No sermons found' ),
				'not_found_in_trash' => __( 'No sermons found in Trash' )
			),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'publicly_queryable' => true,
			'query_var' => false,
			'rewrite' => false,
			'menu_position' => 5,
			'menu_icon' => plugins_url( '/media/bible-icon-16x12.png', $this->plugin_info['plugin_location'] ),
			'map_meta_cap' => true,
			'supports' => array( 'title', 'thumbnail', 'editor', 'comments', 'custom-fields' ),
			'taxonomies' => array( 'tlsp_preacher', 'tlsp_series', 'tlsp_service', 'tlsp_tag' )
			
		);
		if ( is_admin() )
		{
			$args['register_meta_box_cb'] = array( $this, 'metabox_setup' );
		}
		register_post_type( 'sermon_post' , $args );
		
		// To allow custom post type names, we need to add some rewrite rules
		$this->post_rewrite();
		
		// add taxonomy: preacher
		register_taxonomy(
			'tlsp_preacher',
			array( 'sermon_post' ),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name' => _x( 'Preachers', 'taxonomy general name' ),
					'singular_name' => _x( 'Preacher', 'taxonomy singular name' ),
					'search_items' => __( 'Search Preachers' ),
					'all_items' => __( 'All Preachers' ),
					'edit_item' => __( 'Edit Preacher' ), 
					'update_item' => __( 'Update Preacher' ),
					'add_new_item' => __( 'Add New Preacher' ),
					'new_item_name' => __( 'New Preacher Name' ),
				),
				'show_ui' => false,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'preacher' ),
			)
		);
		
		// add taxonomy: series
		register_taxonomy(
			'tlsp_series',
			array('sermon_post'),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name' => _x( 'Sermon Series', 'taxonomy general name' ),
					'singular_name' => _x( 'Sermon Series', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Sermon Series' ),
					'all_items' => __( 'All Sermon Series' ),
					'edit_item' => __( 'Edit Sermon Series' ), 
					'update_item' => __( 'Update Sermon Series' ),
					'add_new_item' => __( 'Add New Sermon Series' ),
					'new_item_name' => __( 'New Sermon Series Name' ),
				),
				'show_ui' => false,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'series' ),
			)
		);
		
		// add taxonomy: service
		register_taxonomy(
			'tlsp_service',
			array('sermon_post'),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name' => _x( 'Services', 'taxonomy general name' ),
					'singular_name' => _x( 'Service', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Services' ),
					'all_items' => __( 'All Services' ),
					'edit_item' => __( 'Edit Service' ), 
					'update_item' => __( 'Update Service' ),
					'add_new_item' => __( 'Add New Service' ),
					'new_item_name' => __( 'New Service Name' ),
				),
				'show_ui' => false,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'service' ),
			)
		);
		
		// add category: sermontag
		register_taxonomy(
			'tlsp_tag',
			array('sermon_post'),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name' => _x( 'Sermon Tags', 'taxonomy general name' ),
					'singular_name' => _x( 'Sermon Tag', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Sermon Tags' ),
					'all_items' => __( 'All Sermon Tags' ),
					'edit_item' => __( 'Edit Sermon Tag' ), 
					'update_item' => __( 'Update Sermon Tag' ),
					'add_new_item' => __( 'Add New Sermon Tag' ),
					'new_item_name' => __( 'New Sermon Tag' ),
				),
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'sermontag' ),
			)
		);
	
	}
	
	/**
	 * Permalink structure rewrite. This is essentially a copy of the get_permalink() function in wp-includes/link-template.php
	 * Many thanks to Shiba for the help: http://shibashake.com/wordpress-theme/custom-post-type-permalinks
	 *
	 * @since 0.16
	 * @author saibotsivad
	*/
	function post_permalink( $permalink, $post_id, $leavename ) {

		$post = get_post( $post_id );

		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename? '' : '%pagename%',
		);

		if ( $post->post_type = 'sermon_post' && '' != $permalink && !in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) )
		{
			$unixtime = strtotime($post->post_date);

			$category = '';
			if ( strpos( $permalink, '%category%' ) !== false ) {
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					usort( $cats, '_usort_terms_by_ID' ); // order by ID
					$category = $cats[0]->slug;
					if ( $parent = $cats[0]->parent )
						$category = get_category_parents( $parent, false, '/', true ) . $category;
				}
				// show default category in permalinks, without
				// having to assign it explicitly
				if ( empty($category) ) {
					$default_category = get_category( get_option( 'default_category' ) );
					$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
				}
			}

			$author = '';
			if ( strpos($permalink, '%author%') !== false ) {
				$authordata = get_userdata($post->post_author);
				$author = $authordata->user_nicename;
			}

			$date = explode(" ",date('Y m d H i s', $unixtime));
			$rewritereplace =
			array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post->post_name,
				$post->ID,
				$category,
				$author,
				$post->post_name,
			);
			$permalink = str_replace($rewritecode, $rewritereplace, $permalink);
		}
		return $permalink;
	}
	
	/**
	 * Rewrite rules to make the URL customizable, aka, better permalinks
	 * @param bool $flush Optional. Whether to flush rewrite rules (rebuilds htaccess, an expensive procedure)
	 *
	 * @since 0.16
	 * @author saibotsivad
	*/
	function post_rewrite( $flush = false )
	{
	
		// the rewrite rule takes the post name, passes it in to the query, and rebuilds it (read the WP docs)
		global $wp_rewrite;
		$wp_rewrite->add_rewrite_tag( "%sermon_post%", '([^/]+)', "post_type=sermon_post&name=" );
		$wp_rewrite->add_permastruct( 'sermon_post', '/sermons/%year%/%monthnum%/%day%/%sermon_post%', false );
		
		if ( $flush )
		{
			flush_rewrite_rules();
		}
	
	}

}