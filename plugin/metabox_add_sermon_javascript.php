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
jQuery(document).ready( function($){
	/* verse saving method */
	$('#tlsp_save_button').click(function(){
		$('#tlsp_save_return').text('Adding...');
		var data = {
			action: 'tlsp_save_reference',
			post_id: <?php echo $post->ID; ?>,
			reference_id: $("#tlsp_verse_table").attr("data-tlsp_reference_id"),
			from_book: $("#tlsp_from_book").val(),
			from_chapter: $("#tlsp_from_chapter").val(),
			from_verse: $("#tlsp_from_verse").val(),
			through_book: $("#tlsp_through_book").val(),
			through_chapter: $("#tlsp_through_chapter").val(),
			through_verse: $("#tlsp_through_verse").val()
		};
		$.post(ajaxurl, data, function(response) {
			if ( response == 'fail' ) {
				$('#tlsp_save_return').text('Invalid verse range!');
			} else {
				var v = $.parseJSON(response);
				/* add new verse to list */
				$('<li/>').appendTo('ul#tlsp_verse_list').html(<?php echo "'" . tlsp_admin::get_html_list_verse(array('reference_id' => "'+v.reference_id+'", 'range_name' => "'+v.reference_name+'")) . "'"; ?>);
				/* clear "saving" text */
				$('#tlsp_save_return').text('');
				tlsp_clear_verse_form_fields();
			}
		});
	});
	/* show/hide controls */
	$('#tlsp_add_verse').click(function(){
		$('#tlsp_verse_table').toggle();
		$('#tlsp_cancel_button').toggle();
		$('#tlsp_add_verse').toggle();
	});
	$('#tlsp_cancel_button').click(function(){
		$('#tlsp_verse_table').toggle();
		$('#tlsp_cancel_button').toggle();
		$('#tlsp_add_verse').toggle();
		tlsp_clear_verse_form_fields();
	});
	/* on clicking edit, it puts the verse in the form to edit it */
	$('.tlsp_verse_range').click(function(){
		$("#tlsp_verse_table").attr("data-tlsp_reference_id", $(this).attr("data-tlsp_reference_id"));
/*		$("#tlsp_from_book").val(),
		$("#tlsp_from_chapter").val(),
		$("#tlsp_from_verse").val(),
		$("#tlsp_through_book").val(),
		$("#tlsp_through_chapter").val(),
		$("#tlsp_through_verse").val()
		
		$('#tlsp_verse_table').toggle();
		$('#tlsp_cancel_button').toggle();
		$('#tlsp_add_verse').toggle();
		tlsp_clear_verse_form_fields(); */
	});
	
});
</script>