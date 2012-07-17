<?php

function tlsp_get_post_verses( $post_id )
{
	return get_post_meta( $post_id, 'tlsp_sermon_reference', true );
}

// returns an array of verse ranges and range names from mysql db
function tlsp_get_post_verses_mysql( $post_id )
{
	global $wpdb;
	$query = "SELECT
		%d AS post_id,
		r.id AS reference_id,
		bf.name AS from_name,
		vf.id AS from_id,
		vf.book AS from_book,
		vf.chapter AS from_chapter,
		vf.verse AS from_verse,
		
		bt.name AS through_name,
		vt.id AS through_id,
		vt.book AS through_book,
		vt.chapter AS through_chapter,
		vt.verse AS through_verse
	FROM
		wp_tlsp_reference AS r
	JOIN
		wp_tlsp_bible AS vf ON vf.id = r.start
	JOIN
		wp_tlsp_book AS bf ON bf.id = vf.book
	JOIN
		wp_tlsp_bible AS vt ON vt.id = r.end
	JOIN
		wp_tlsp_book AS bt ON bt.id = vt.book
	WHERE
		r.sermon = %d";
	
	$results = $wpdb->get_results( $wpdb->prepare( $query, $post_id, $post_id ), ARRAY_A );
	$new_results = array();
	if ( !empty( $results ) )
	{
		foreach ( $results as $result )
		{
			$verse_range_name = tlsp_generate_verse_range_name( $result['from_name'], $result['from_chapter'], $result['from_verse'], $result['through_name'], $result['through_chapter'], $result['through_verse'] );
			$result['range_name'] = $verse_range_name;
			$new_results[] = $result;
		}
	}
	return $new_results;
}

// helper: construct the name of the verse range, e.g., "Genesis 1:3-17"
function tlsp_generate_verse_range_name( $from_name, $from_chapter, $from_verse, $through_name, $through_chapter, $through_verse )
{
	$name = $from_name ." ". $from_chapter .":". $from_verse;
	if ( $from_name == $through_name )
	{
		if ( $from_chapter == $through_chapter )
		{
			if ( $from_verse != $through_verse )
			{
				$name .= "-". $through_verse;
			}
		}
		else
		{
			$name .= "-". $through_chapter .":". $through_verse;
		}
	}
	else
	{
		$name .= " - ". $through_name ." ". $through_chapter .":". $through_verse;
	}
	return $name;
}

function tlsp_generate_verse_range( $from_book, $from_chapter, $from_verse, $through_book, $through_chapter, $through_verse )
{
	// make sure the verse range is valid
	global $wpdb;
	$query = "SELECT
			bf.name AS from_name,
			vf.id AS from_id,
			vf.book AS from_book,
			vf.chapter AS from_chapter,
			vf.verse AS from_verse,
			
			bt.name AS through_name,
			vt.id AS through_id,
			vt.book AS through_book,
			vt.chapter AS through_chapter,
			vt.verse AS through_verse
		FROM
			wp_tlsp_bible AS vf,
			wp_tlsp_book AS bf,
			wp_tlsp_bible AS vt,
			wp_tlsp_book AS bt
		WHERE
			vf.book = %d
			AND vf.chapter = %d
			AND vf.verse = %d
			AND vt.book = %d
			AND vt.chapter = %d
			AND vt.verse = %d
			AND bf.id = vf.book
			AND bt.id = vt.book";
	$verse_query = $wpdb->prepare( $query, $from_book, $from_chapter, $from_verse, $through_book, $through_chapter, $through_verse );
	$verse = $wpdb->get_row( $verse_query, ARRAY_A );
	if ( $verse == null )
	{
		return null;
	}
	
	// get the name
	$name = tlsp_generate_verse_range_name( $verse['from_name'], $verse['from_chapter'], $verse['from_verse'], $verse['through_name'], $verse['through_chapter'], $verse['through_verse'] );
	
	// create array output
	$output = array(
		'reference_name'      => $name,
		'from_id'         => $verse['from_id'],
		'from_name'       => $verse['from_name'],
		'from_book'       => $verse['from_book'],
		'from_chapter'    => $verse['from_chapter'],
		'from_verse'      => $verse['from_verse'],
		'through_id'      => $verse['through_id'],
		'through_name'    => $verse['through_book'],
		'through_book'    => $verse['through_book'],
		'through_chapter' => $verse['through_chapter'],
		'through_verse'   => $verse['through_verse']
	);
	return $output;
}

function tlsp_create_html_verse_range_form($verse_range) {
		$defaults = array(
			'id' => 0,
			'verse_id' => 0,
			'name' => '',
			'from_id' => 0,
			'from_book' => 0,
			'from_chapter' => 0,
			'from_verse' => 0,
			'through_id' => 0,
			'through_book' => 0,
			'through_chapter' => 0,
			'through_verse' => 0
		);
		$v = array_merge( $defaults, $verse_range );
		
		// this particular coding style is used to remove extra whitespace (JS safe)
		$html =
		'<table class="tlsp_verse_table" id="tlsp_verse_table_'.$v['id'].'">' +
			'<tbody>' +
				'<tr>' +
					'<td colspan="2">' +
						'<span class="tlsp_verse_range_name">'.$v['name'].'</span>' +
						'<span class="alignright"><a class="tlsp_edit_cancel_button" id="tlsp_edit_cancel_button_'.$v['verse_id'].'" data-tlsp_verse_id="'.$v['verse_id'].'">Edit</a></span>' +
					'</td>' +
				'</tr>' +
				'<tr class="tlsp_verse_range" id="tlsp_from_'.$v['id'].'">' +
					'<td>From:</td>' +
					'<td class="alignright">' +
						'<select class="tlsp_from_book">'.tlsp_create_html_book_select($v['from_book']).'</select>' +
						'<input type="text" value="'.$v['from_chapter'].'" class="tlsp_from_chapter" name="tlsp_from_chapter" maxlength="3" size="1">' +
						'<input type="text" value="'.$v['from_verse'].'" class="tlsp_from_verse" name="tlsp_from_verse" maxlength="3" size="1">' +
					'</td>' +
				'</tr>' +
				'<tr class="tlsp_verse_range" id="tlsp_through_'.$v['verse_id'].'">' +
					'<td>Through:</td>' +
					'<td class="alignright">' +
						'<select class="tlsp_through_book">'.tlsp_create_html_book_select($v['through_book']).'</select>' +
						'<input type="text" value="'.$v['through_chapter'].'" class="tlsp_through_chapter" name="tlsp_through_chapter" maxlength="3" size="1">' +
						'<input type="text" value="'.$v['through_verse'].'" class="tlsp_through_verse" name="tlsp_through_verse" maxlength="3" size="1">' +
					'</td>' +
				'</tr>' +
				'<tr class="tlsp_verse_range"><td colspan="2"><a class="tlsp_delete_verse" data-tlsp_verse_id="'.$v['verse_id'].'" data-tlsp_verse_from="'.$v['from_id'].'" data-tlsp_verse_through="'.$v['through_id'].'">Delete</a><a class="tlsp_save_button" data-tlsp_verse_id="'.$v['verse_id'].'">Save</a></td></tr>' +
			'</tbody>' +
		'</table>';
		
		return $html;
}

function tlsp_create_html_book_select($x) {
	return "x";
}


/**
 * Sermon Posts Functions
 *
*/
/*
function tlsp_post_passages( $post_id, $return_format = 'array' )
{

}
*/

/**
 * $pre.$bookname.$mid.$bookid.$post
 *
 *
*/
/*
function tlsp_bible_names( $return_format = 'array', $post_id = 1 )
{
	// possible output styles: html-list, html-select, array, json
	// if the post_id is set, you'll get an array of outputs, totalling the
	// number of passages for that sermon, and the sermons selected book will be noted
	
	$bible_names = array();
	
	// get list of bible names
	global $wpdb;
	$bible_names = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sermon_biblebook" );

array
  0 => 
    object(stdClass)[127]
      public 'id' => string '1' (length=1)
      public 'name' => string 'Genesis' (length=7)

	
	$verse_output = array();
	
	if ( $post_id )
	{
		$verses = get_post_meta( $post_id, 'sermon_post_passages' );
		if ( !empty( $verses ) )
		{
			$verses = $verses[0];
			
			foreach ( $verses as $id => $verse )
			{
			
				// start verse
				
				
				echo 'x';
				// erase
				$x = null;
				$copy_bible_names = null;
				// x is the single verse object
				$x = $bible_names[ $verse['start']['book_id'] - 1 ];
				// note as selected
				$x->selected = true;
				// copy the list of bible names
				$copy_bible_names = $bible_names;
				// replace the selected
				$copy_bible_names[ $verse['start']['book_id'] - 1 ] = $x;
				// add list of books to total list
				$verse_output[$id]['start'] = $copy_bible_names;
				
				// repeat for end verse of range
				$x = null;
				$copy_bible_names = null;
				$x = $bible_names[ $verse['end']['book_id'] - 1 ];
				$x->selected = true;
				$copy_bible_names = $bible_names;
				$copy_bible_names[ $verse['end']['book_id'] - 1 ] = $x;
				$verse_output[$id]['end'] = $copy_bible_names;
			
			}
			
			var_dump( $verse_output[1] );
		
		}
	}
	
	// create proper output
	
	
	//return $bible_names;

}
*/