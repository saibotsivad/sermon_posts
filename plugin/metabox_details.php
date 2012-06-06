<?php

if( !current_user_can( 'edit_posts' ) ) die();

// initialize variables
global $post;
global $wpdb;
$html = array();

// default taxonomies are stored on a per-user basis
$options = get_user_meta( get_current_user_id(), "plugin_tlsp_options" );

// post meta data
$post_verses = get_post_meta( $post->ID, 'tlsp_passages' );

// complete taxonomy lists
$taxes['tlsp_service']  = get_terms( 'tlsp_service',  array( 'get' => 'all' ) );
$taxes['tlsp_series']   = get_terms( 'tlsp_series',   array( 'get' => 'all' ) );
$taxes['tlsp_preacher'] = get_terms( 'tlsp_preacher', array( 'get' => 'all' ) );

// associated taxonomy terms
$taxterms['tlsp_service']  = get_the_terms( $post->ID, 'tlsp_service' );
$taxterms['tlsp_series']   = get_the_terms( $post->ID, 'tlsp_series' );
$taxterms['tlsp_preacher'] = get_the_terms( $post->ID, 'tlsp_preacher' );

// create html <select> lists from the taxonomies, with the optionally selected term set
foreach ( $taxes as $name => $tax )
{
	// clear for each loop
	$html[$name] = '';
	
	$html[$name] .= '<select style="width:268px;" name="'.$name.'">';
	$html[$name] .= '<option value="0"></option>';
	foreach ( $tax as $term )
	{
		if ( !empty( $taxterms[$name] ) )
		{
			$selected = ( $term->term_id == $taxterms[$name][0]->term_id ? ' selected="yes"' : '' );
			$html[$name] .= "<option value='{$term->term_id}'{$selected}>{$term->name}</option>";
		}
		else
		{
			$pre_text = '';
			$selected = '';
			// only auto-select the taxonomy if the current view is the "Add New" page and there is a default picked
			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'sermon_post' && isset( $options['default_options'][$name]['val'] ) && $options['default_options'][$name]['val'] == $term->term_id )
			{
				$pre_text = "Default: ";
				$selected = " selected='yes'";
			}
			$html[$name] .= "<option value='{$term->term_id}'{$selected}>{$pre_text}{$term->name}</option>";
		}
	}
	$html[$name] .= '</select>';

}

// date html: if you use the appropriate html names, wordpress will save it automagically
$timestamp = strtotime( get_the_date() );
$real_day = date( 'd', $timestamp );
$real_year = date( 'Y', $timestamp );
$real_month = date( 'F', $timestamp );
// month as a <select>
$html['date'] = '<select id="mm" name="mm" tabindex="4">';
$i = 1;
do {
	$month = date('F', mktime(0,0,0, $i, 1, 2010) ); // the year doesn't matter, we only use the month
	$selected = ( $real_month == $month ? ' selected="selected"' : ' ' );
	$month_number = ( $i < 10 ? "0".$i : $i );
	$html['date'] .= '<option'. $selected .' value="'. $month_number .'">'. $month .'</option>';
	$i++;
} while ( $i < 13 );
$html['date'] .= '</select>';
// day and time
$html['date'] .= '<input id="jj" name="jj" value="'. $real_day .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text">, <input id="aa" name="aa" value="'. $real_year .'" size="4" maxlength="4" tabindex="4" autocomplete="off" type="text"> @ <input id="hh" name="hh" value="'. get_the_time( 'H', $post->ID ) .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text"> : <input id="mn" name="mn" value="'. get_the_time( 'i', $post->ID ) .'" size="2" maxlength="2" tabindex="4" autocomplete="off" type="text">';

// get the list of bible names and create a json array and an html select list
$book_json = array();
$html['book'] = "<OPTION VALUE=''></OPTION>";
global $wpdb;
$books = $wpdb->get_results( "SELECT `id` AS `id`, `name` AS `name` FROM " . $wpdb->prefix . $this->table_names['books'] );
foreach ( $books as $key => $book )
{
	$book_json[$book->id] = $book->name;
	$html['book'] .= "<OPTION VALUE='{$book->id}'>{$book->name}</OPTION>";
}
$book_json = json_encode( $book_json );

// format the list of passages associated with this post


/* ===== output starts here ===== */

// JS to handle passages
?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	$('#tlsp_passage_reference_add').click( function () {
		$('#tlsp_passage_reference_add_return').text('Adding...');
		var data = {
			action: 'tlsp_reference_verification',
			tlsp_from_bible_book: $("#tlsp_from_bible_book").val(),
			tlsp_from_bible_chapter: $("#tlsp_from_bible_chapter").val(),
			tlsp_from_bible_verse: $("#tlsp_from_bible_verse").val(),
			tlsp_through_bible_book: $("#tlsp_through_bible_book").val(),
			tlsp_through_bible_chapter: $("#tlsp_through_bible_chapter").val(),
			tlsp_through_bible_verse: $("#tlsp_through_bible_verse").val()
		};
		$.post(ajaxurl, data, function(response) {
			if ( response == 'fail' ) {
				$('#tlsp_passage_reference_add_return').text('Invalid verse range!');
			} else {
				/* add the response to the table of existing passages */
				$('#tlsp_passage_reference_add_return').text(response);
			}
		});
	});
	$('.tlsp_button').click( function () {
		$('#'+this.id).text('Working...');
		var data = {
			action: 'tlsp_sermon_reference_lookup',
			tlsp_post_passage: this.id,
			tlsp_post_id: <?php echo $post->ID; ?>
		};
		$.post(ajaxurl, data, function(response) {
			if ( response == 'fail' ) {
				$('#tlsp_passage_reference_add').text('Edit Passage');
			} else {
				var verses = $.parseJSON(response);
				$("#tlsp_from_bible_book").val(verses.from_book),
				$("#tlsp_from_bible_chapter").val(verses.from_chapter),
				$("#tlsp_from_bible_verse").val(verses.from_verse),
				$("#tlsp_through_bible_book").val(verses.through_book),
				$("#tlsp_through_bible_chapter").val(verses.through_chapter),
				$("#tlsp_through_bible_verse").val(verses.through_verse)
			}
		});
		$('#'+this.id).val("Edit");
		$('#'+this.id).removeClass("failure").addClass("success");
	});
});
</script>
<script type="text/javascript">

</script><?php
	
// CSS styling
?><style>

/* hide the default date picker */ 
div.curtime{display: none !important;}

/* hide the upload/insert button area */
div#wp-content-media-buttons{display: none !important;}

/* style the passages section */
div#tlsp_sermonpassage table {
	border: 1px solid #DFDFDF;
	padding: 4px;
	-moz-border-radius: 4px 4px 4px;
}
div#tlsp_sermonpassage td {
	padding: 0;
	margins: 0;
}
div#tlsp_sermonpassage select {
	width: 120px;
}
table#tlsp_addmore_table {
	border: 1px solid #DFDFDF;
	padding: 8px;
	margin: 0;
	width: 100%;
	-moz-border-radius: 4px 4px 4px;
	float: right;
}
table#tlsp_existing_passages {
	width: 100%;
	border: 1px solid #DFDFDF;
	-moz-border-radius: 4px 4px 4px;
}
td.tlsp_passage_edit_row {
	width: auto;
	padding: 0 6px 0 0;
	margin: 0;
	text-align: right;
}
table#tlsp_addmore_table td {
	padding: 3px 0 3px 0;
	margin: 0;
}
td#tlsp_addmore_button {
	padding: 6px 0 0 0 !important;
	text-align: right;
}
td.tlsp_passage_row {
	text-align: right;
	padding-right: 0;
}
a.tlsp_button {
	border: 1px solid #BBBBBB;
	-moz-border-radius: 4px 4px 4px;
	padding: 3px 8px;
	color: #464646;
	background-color: #FFFFFF;
}
a.tlsp_passage_remove_button {
	padding-left: 12px;
	color: red;
}
td#tlsp_passage_reference_add_return {
	text-align: right;
}
</style><?php

// use a nonce field for data entry authentication
wp_nonce_field( 'tlsp_metabox_save', 'tlsp_metabox_save' );

?>
<table class="form-table">
	<tbody>
		<tr>
			<td>Preacher:</td>
			<td><?php echo $html['tlsp_preacher']; ?></td>
		</tr>
		<tr>
			<td>Series:</td>
			<td><?php echo $html['tlsp_series']; ?>	</td>
		</tr>
		<tr>
			<td>Service:</td>
			<td><?php echo $html['tlsp_service']; ?></td>
		</tr>
		<tr>
			<td>Date:</td>
			<td><?php echo $html['date']; ?></td>
		</tr>
		<tr>
			<td>Bible Passage(s):</td>
			<td>
				<div class="hide-if-js">You need to enable JavaScript to add or remove Bible passages to the sermon.</div>
				<table id="tlsp_existing_passages"><tbody>
					<tr id="tlsp_passage_row_1"><td class="tlsp_passage">Genesis 1:1-3:17</td><td class="tlsp_passage_edit_row"><a class="tlsp_button" id="tlsp_post_passage_1">Edit</a> <a class="tlsp_passage_remove_button">Remove</a></td></tr>
					<tr id="tlsp_passage_row_2"><td class="tlsp_passage">Exodus 3-5</td><td class="tlsp_passage_edit_row"><a class="tlsp_button" id="tlsp_post_passage_2">Edit</a> <a class="tlsp_passage_remove_button">Remove</a></td></tr>
				</tbody></table>
			</td>
		</tr>
		<tr id="tlsp_addmore_tableform">
			<td>Add Passage:</td>
			<td>
				<table id="tlsp_addmore_table"><tbody>
					<tr>
						<td class="tlsp_addmore_title">From:</td>
						<td class="alignright">
							<select id="tlsp_from_bible_book"><?php echo $html['book']; ?></select>
							<input type="text" value="" id="tlsp_from_bible_chapter" name="tlsp_bible_chapter" maxlength="3" size="2">
							<input type="text" value="" id="tlsp_from_bible_verse" name="tlsp_bible_verse" maxlength="3" size="2">
						</td>
					</tr>
					<tr>
						<td class="tlsp_addmore_title">Through:</td>
						<td class="alignright">
							<select id="tlsp_through_bible_book"><?php echo $html['book']; ?></select>
							<input type="text" value="" id="tlsp_through_bible_chapter" name="tlsp_bible_chapter" maxlength="3" size="2">
							<input type="text" value="" id="tlsp_through_bible_verse" name="tlsp_bible_verse" maxlength="3" size="2">
						</td>
					</tr>
					<tr>
						<td colspan="2" id="tlsp_addmore_button"><a class="tlsp_button" id="tlsp_passage_reference_add">Add Passage</a></td>
					</tr>
					<tr>
						<td colspan="2" id="tlsp_passage_reference_add_return"></td>
					</tr>
				</tbody></table>
			</td>
		</tr>
	</tbody>
</table>








