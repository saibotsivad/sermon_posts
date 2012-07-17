<?php

if( !current_user_can( 'edit_posts' ) ) die();

include( 'metabox_add_sermon_javascript.php' );

wp_nonce_field( 'tlsp_metabox_save', 'tlsp_metabox_save' );

?>
<table class="form-table">
	<tbody>
		<tr>
			<td>Preacher:</td>
			<td><?php echo tlsp_admin::get_html_select( 'tlsp_preacher' ); ?>
			</td>
		</tr>
		<tr>
			<td>Series:</td>
			<td><?php echo tlsp_admin::get_html_select( 'tlsp_series' ); ?>	</td>
		</tr>
		<tr>
			<td>Service:</td>
			<td><?php echo tlsp_admin::get_html_select( 'tlsp_service' ); ?></td>
		</tr>
		<tr>
			<td>Date:</td>
			<td><?php echo tlsp_admin::get_html_date(); ?></td>
		</tr>
		<tr>
			<td>Bible Passage(s):</td>
			<td id="tlsp_verse_list">
				<?php echo tlsp_admin::get_html_list_verses(); ?>
				<p><a class="button" id="tlsp_add_verse">Add New Verse</a><a class="button" id="tlsp_cancel_button" style="display:none;">Cancel</a></p>
				<table id="tlsp_verse_table" style="display:none;" data-tlsp_reference_id=""><tbody>
					<tr><td colspan="2" id="tlsp_reference_name"></td></tr>
					<tr>
						<td>From:</td>
						<td class="alignright">
							<select id="tlsp_from_book"><?php echo tlsp_admin::get_html_select_options_bible_books(); ?></select>
							<input type="text" id="tlsp_from_chapter" name="tlsp_from_chapter" maxlength="3" size="1">
							<input type="text" id="tlsp_from_verse" name="tlsp_from_verse" maxlength="3" size="1">	
						</td>
					</tr>
					<tr>
						<td>Through:</td>
						<td class="alignright">
							<select id="tlsp_through_book"><?php echo tlsp_admin::get_html_select_options_bible_books(); ?></select>
							<input type="text" id="tlsp_through_chapter" name="tlsp_through_chapter" maxlength="3" size="1">
							<input type="text" id="tlsp_through_verse" name="tlsp_through_verse" maxlength="3" size="1">
						</td>
					</tr>
					<tr><td colspan="2"><span id="tlsp_save_return"></span><a class="button" id="tlsp_save_button">Save</a></td></tr>
				</tbody></table>
			</td>
		</tr>
	</tbody>
</table>








