<?php
global $post;
?>
<script type="text/javascript">
function tlsp_clear_verse_form_fields(){
	jQuery("#tlsp_verse_table").attr("data-tlsp_reference_id", '');
	jQuery("#tlsp_from_book").val('0');
	jQuery("#tlsp_from_chapter").val('');
	jQuery("#tlsp_from_verse").val('');
	jQuery("#tlsp_through_book").val('0');
	jQuery("#tlsp_through_chapter").val('');
	jQuery("#tlsp_through_verse").val('');
}
function tlsp_save_verse_fields(){
	jQuery('#tlsp_save_return').text('Saving...');
	var data = {
		action: 'tlsp_save_reference',
		post_id: <?php echo $post->ID; ?>,
		reference_id: jQuery("#tlsp_verse_table").attr("data-tlsp_reference_id"),
		from_book: jQuery("#tlsp_from_book").val(),
		from_chapter: jQuery("#tlsp_from_chapter").val(),
		from_verse: jQuery("#tlsp_from_verse").val(),
		through_book: jQuery("#tlsp_through_book").val(),
		through_chapter: jQuery("#tlsp_through_chapter").val(),
		through_verse: jQuery("#tlsp_through_verse").val()
	};
	jQuery.post(ajaxurl, data, function(response){
		if ( response == 'fail' ) {
			jQuery('#tlsp_save_return').text('Invalid verse range!');
		} else {
			var v = jQuery.parseJSON(response);
			/* if the list element does not already exist, make new list */
			if ( jQuery('#tlsp_reference_'+v.reference_id).length == 0 ){
				jQuery('#tlsp_verse_list').append(<?php echo "'" . tlsp_admin::get_html_list_verse(array('reference_id' => "'+v.reference_id+'", 'range_name' => "'+v.reference_name+'")) . "'"; ?>);
				jQuery('.tlsp_edit_verse').click(tlsp_lookup_reference);
				jQuery('.tlsp_delete_verse').click(tlsp_delete_reference);
			} else {
				jQuery('#tlsp_reference_'+v.reference_id).replaceWith(<?php echo "'" . tlsp_admin::get_html_list_verse(array('reference_id' => "'+v.reference_id+'", 'range_name' => "'+v.reference_name+'")) . "'"; ?>);
				jQuery('.tlsp_edit_verse').click(tlsp_lookup_reference);
				jQuery('.tlsp_delete_verse').click(tlsp_delete_reference);
			}
			/* clear "saving" text */
			jQuery('#tlsp_save_return').text('');
			tlsp_toggle_verse_table();
		}
	});
}
function tlsp_toggle_verse_table(){
	jQuery('#tlsp_verse_table').toggle();
	jQuery('#tlsp_cancel_button').toggle();
	jQuery('#tlsp_add_verse').toggle();
	tlsp_clear_verse_form_fields();
}
function tlsp_lookup_reference(){
	var data = {
		action: 'tlsp_lookup_reference',
		reference_id: jQuery(this).parent().parent().attr("data-tlsp_reference_id")
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( response == 'fail' ) {
			jQuery('#tlsp_save_return').text('Unhelpful error!');
		} else {
			var v = jQuery.parseJSON(response);
			jQuery("#tlsp_verse_table").attr("data-tlsp_reference_id", v.reference_id);
			jQuery("#tlsp_from_book").val(v.from_book);
			jQuery("#tlsp_from_chapter").val(v.from_chapter);
			jQuery("#tlsp_from_verse").val(v.from_verse);
			jQuery("#tlsp_through_book").val(v.through_book);
			jQuery("#tlsp_through_chapter").val(v.through_chapter);
			jQuery("#tlsp_through_verse").val(v.through_verse);
			// show the table last
			jQuery('#tlsp_verse_table').toggle();
			jQuery('#tlsp_cancel_button').toggle();
			jQuery('#tlsp_add_verse').toggle();
		}
	});
}
function tlsp_delete_reference(){
	var data = {
		action: 'tlsp_delete_reference',
		post_id: <?php echo $post->ID; ?>,
		reference_id: jQuery(this).parent().parent().attr("data-tlsp_reference_id")
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( response == 'fail' ) {
			jQuery('#tlsp_save_return').text('Unhelpful error!');
		} else {
			var r = jQuery.parseJSON(response);
			if (r.response == true){
				jQuery('#tlsp_reference_'+r.reference_id).remove();
			}
		}
	});
}
jQuery(document).ready(function($){
	$('#tlsp_save_button').click(tlsp_save_verse_fields);
	$('#tlsp_add_verse').click(tlsp_toggle_verse_table);
	$('#tlsp_cancel_button').click(tlsp_toggle_verse_table);
	$('.tlsp_edit_verse').click(tlsp_lookup_reference);
	$('.tlsp_delete_verse').click(tlsp_delete_reference);
});
</script>