<?php

class tlsp_admin
{
	public static function get_html_select($name)
	{
		return '<select style="width:268px;" name="'.$name.'">'.self::get_html_select_options($name).'</select>';
	}
	
	private static function get_html_select_options($name)
	{
		global $post;
		$options = get_terms( $name, array( 'get' => 'all' ) );
		$post_options = get_the_terms( $post->ID, $name );
		
		$html =  '<option value="0"></option>';
		foreach ( $options as $option )
		{
			$selected = ( $post_options && $option->term_id == $post_options[0]->term_id ? ' selected="yes"' : '' );
			$html .= "<option value='{$option->term_id}'{$selected}>{$option->name}</option>";
		}
		return $html;
	}
	
	public static function get_html_date()
	{
		// if you use the appropriate html names, wordpress will save it automagically
		
		$timestamp = strtotime( get_the_date() );
		
		$real_day = date( 'd', $timestamp );
		$real_year = date( 'Y', $timestamp );
		$real_month = date( 'F', $timestamp );
		
		$html = '<select id="mm" name="mm" tabindex="4">';
		foreach ( self::get_html_date_months_array() as $key => $month )
		{
			$selected = ( $real_month == $month ? ' selected="yes"' : '' );
			$html .= '<option'. $selected .' value="'. $key .'">'. $month .'</option>';
		}
		$html .= '</select>';
		
		global $post;
		$html .= '<input id="jj" name="jj" value="'. $real_day .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text">, <input id="aa" name="aa" value="'. $real_year .'" size="4" maxlength="4" tabindex="4" autocomplete="off" type="text"> @ <input id="hh" name="hh" value="'. get_the_time( 'H', $post->ID ) .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text"> : <input id="mn" name="mn" value="'. get_the_time( 'i', $post->ID ) .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text">';
		
		return $html;
	}
	
	private static function get_html_date_months_array()
	{
		return array(
			'01' => 'January',
			'02' => 'February',
			'03' => 'March',
			'04' => 'April',
			'05' => 'May',
			'06' => 'June',
			'07' => 'July',
			'08' => 'August',
			'09' => 'September',
			'10' => 'October',
			'11' => 'November',
			'12' => 'December'
		);
	}
	
	public static function get_html_list_verses()
	{
		global $post;
		$verses = tlsp_get_post_verses_mysql( $post->ID );
		//var_dump( $verses );
		$html = '<ul id="tlsp_verse_list">';
		if( !empty( $verses ) )
		{
			foreach ( $verses as $key => $verse )
			{
				$html .= self::get_html_list_verse( $verse );
			}
		}
		$html .= '</ul>';
		return $html;
	}
	
	public static function get_html_list_verse( $verse )
	{
		return
			'<li class="tlsp_verse_range" data-tlsp_reference_id="'.$verse['reference_id'].'">'.
				$verse['range_name'].
				'<input type="hidden" name="tlsp_verse_ranges[]" value="'.$verse['reference_id'].'">'.
				'<span class="tlsp_verse_controls"><a class="tlsp_edit_verse">Edit</a> or <a class="tlsp_delete_verse">Delete</a></span>'.
			'</li>';
	}
	
	public static function get_html_select_options_bible_books($name = '')
	{
		global $wpdb;
		$query = "SELECT `id` AS `id`, `name` as `name` FROM `wp_tlsp_book` ORDER BY `id`";
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		$html = '<option value="0"></option>';
		foreach ( $results as $result )
		{
			$selected = ( ( (intval($result['id']) === $name) || ($result['name'] === $name) ) ? ' selected="yes"' : '' );
			$html .= '<option value="'.$result['id'].'"'.$selected.'>'.$result['name'].'</option>';
		}
		
		return $html;
	}
	
	public static function save_sermon_verse_range( $verse )
	{
		return self::save_sermon_verse_range_postmeta( self::save_sermon_verse_range_mysql( $verse ) );
	}
	
	public static function save_sermon_verse_range_postmeta( $verse )
	{
		$sermon_verses = get_post_meta( $verse['post_id'], 'tlsp_sermon_reference', true );
		$sermon_verses = ( is_array( $sermon_verses ) ? $sermon_verses : array() );
		$sermon_verses[] = $verse;
		update_post_meta( $verse['post_id'], 'tlsp_sermon_reference', $sermon_verses );
		return $verse;
	}
	
	public static function save_sermon_verse_range_mysql( $verse )
	{
		global $wpdb;
		$query = "INSERT INTO `wp_tlsp_reference` (`sermon`, `start`, `end`)
			VALUES (%d, %d, %d)
			ON DUPLICATE KEY UPDATE `start` = %d, `end` = %d";
		$wpdb->insert(
			'wp_tlsp_reference',
			array( 'sermon' => $verse['post_id'], 'start' => $verse['from_id'], 'end' => $verse['through_id'] ),
			array( '%d', '%d', '%d' )
		);
		//$wpdb->get_results( $wpdb->prepare( $query, $verse['post_id'], $verse['from_id'], $verse['through_id'], $verse['from_id'], $verse['through_id'] ), ARRAY_A );
		$verse['reference_id'] = $wpdb->insert_id;
		return $verse;
	}
}