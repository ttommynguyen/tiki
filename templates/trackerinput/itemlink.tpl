{* $Id$ *}
<div class="item-link" id="il{$field.ins_id}">
	{if $data.selectMultipleValues}
		<input type="hidden" name="{$field.ins_id}_old" value="{$field.value|escape}" />
	{/if}
	{if $data.displayFieldsListType === 'table'}
		{capture assign=fieldvalue}{if is_array($field.value)}{','|implode:$field.value}{else}{$field.value}{/if}{/capture}
		{wikiplugin _name=trackerlist trackerId=$field.options_map.trackerId fields=':'|implode:$field.options_map.displayFieldsList editableall="y" showlinks="y" sortable="type:reset" sortList="[1,0]" tsfilters="type:nofilter" tsfilteroptions="type:reset" tspaginate="max:5" checkbox="/"|cat:$field.ins_id|cat:"//////y/"|cat:$fieldvalue filterfield=$field.options_map.preSelectFieldThere exactvalue=$data.preselection_value ignoreRequestItemId=y}
		{/wikiplugin}
		{if $field.options_map.addItems}
			<a class="btn btn-default insert-tracker-item" href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId next=$data.next|escape}" data-href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId next=$data.next|escape}">{$field.options_map.addItems|escape}</a>
			{if $field.options_map.preSelectFieldThere}
			{jq}
				$("#il{{$field.ins_id}}").find('.insert-tracker-item').on('click', function() {
					$(this).attr('href', $(this).data('href')+'&ins_{{$field.options_map.preSelectFieldThere}}='+$('#ins_{{$field.options_map.preSelectFieldHere}}').val());
				});
			{/jq}
			{/if}
			{jq}
				$("#il{{$field.ins_id}}")
					.find('.insert-tracker-item')
					.clickModal({
						success: function (data) {
							var displayed = {{$data.list|json_encode}};
							var row = '<tr><td><input type="checkbox" class="{{$field.ins_id}}-checkbox" name="{{$field.ins_id}}[]" value="'+data.itemId+'" checked /></td>';
							$.each(displayed, function(fieldId, permName) {
								if( $('#il{{$field.ins_id}} th').filter(function(i, el){ return $(el).hasClass('field'+fieldId); }).length > 0 ) {
									row += '<td>'+data.processedFields[permName]+'</td>';
								}
							});
							row += '</tr>';
							$row = $(row);
							$('#il{{$field.ins_id}} table')
								.find('tbody').append($row)
								.trigger('addRows', [$row, true]);
							$.closeModal();
						}
					});
			{/jq}
		{/if}
	{else}
		<select name="{$field.ins_id}{if $data.selectMultipleValues}[]{/if}" {if $data.preselection and $data.crossSelect neq 'y'}disabled="disabled"{/if} {if $data.selectMultipleValues}multiple="multiple"{/if} class="form-control">
			{if $field.isMandatory ne 'y' || empty($field.value)}
				<option value=""></option>
			{/if}
			{foreach key=id item=label from=$data.list}
				<option value="{$id|escape}" {if $data.preselection and !$field.value and $data.preselection eq $id or (($data.selectMultipleValues and is_array($field.value) and in_array($id, $field.value) or $field.value eq $id))}selected="selected"{/if}>
					{$label|escape}
				</option>
			{/foreach}
		</select>
		{if $field.options_map.addItems}
			<a class="btn btn-default insert-tracker-item" href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId next=$data.next|escape}" data-href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId next=$data.next|escape}">{$field.options_map.addItems|escape}</a>
			{if $field.options_map.preSelectFieldThere}
			{jq}
				$("#il{{$field.ins_id}}").find('.insert-tracker-item').on('click', function() {
					$(this).attr('href', $(this).data('href')+'&ins_{{$field.options_map.preSelectFieldThere}}='+$('#ins_{{$field.options_map.preSelectFieldHere}}').val());
				});
			{/jq}
			{/if}
			{jq}
				$("select[name={{$field.ins_id}}]").next().clickModal({
					success: function (data) {
						$('<option>')
							.attr('value', data.itemId)
							.text(data.itemTitle)
							.appendTo($(this).prev());
						$(this).prev().val(data.itemId);
						$(this).prev().trigger("change");
						$.closeModal();
					}
				});
			{/jq}
		{/if}
	{/if}
</div>
