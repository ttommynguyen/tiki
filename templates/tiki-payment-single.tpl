<div {if isset($iPluginMemberpayment)}id="pluginMemberpayment{$iPluginMemberpayment}" {elseif isset($datachannel_execution)}id="{$datachannel_execution}" {/if}class="payment">
	{if isset($wp_member_title)}
		{wiki}{tr 0=$wp_member_group.groupName 4=$wp_member_group.expireAfter 5=$wp_member_group.expireAfterYear}{$wp_member_title}{/tr}{/wiki}
	{else} 
		<h2>{$payment_info.description|escape}</h2>
	{/if}
	<p>{tr}Status{/tr}: <strong>{$payment_info.state|escape}</strong></p>
	{if $payment_info.fullview}
		<div class="clearfix wikitext">
			{$payment_detail}
		</div>
	{/if}
	<p>
		{if $payment_info.state eq 'past'}
			{tr}Paid amount{/tr}: {$payment_info.amount_original|escape} {$payment_info.currency|escape}<br />
		{else}
			{tr}Initial amount{/tr}: {$payment_info.amount_original|escape} {$payment_info.currency|escape}<br />
			{tr}Amount remaining{/tr}: <strong>{$payment_info.amount_remaining|escape} {$payment_info.currency|escape}</strong><br />
			{tr 0=$payment_info.request_date|tiki_short_date 1=$payment_info.due_date|tiki_short_date}Payment request was sent on %0 and is due by %1.{/tr}<br />
		{/if}
		{if ( $payment_info.state eq 'outstanding' || $payment_info.state eq 'overdue' )}
			{if $prefs.payment_system eq 'paypal' && $prefs.payment_paypal_business neq ''}
				<form action="{$prefs.payment_paypal_environment|escape}" method="post">
					<input type="hidden" name="business" value="{$prefs.payment_paypal_business|escape}" />
					<input type="hidden" name="cmd" value="_xclick" />
					<input type="hidden" name="item_name" value="{$payment_info.description|escape}" />
					<input type="hidden" name="amount" value="{$payment_info.amount_remaining_raw|escape}" />
					<input type="hidden" name="currency_code" value="{$prefs.payment_currency|escape}" />
					<input type="hidden" name="invoice" value="{$prefs.payment_invoice_prefix|escape}{$payment_info.paymentRequestId|escape}" />
					<input type="hidden" name="return" value="{$payment_info.url|escape}" />
					{*<input type="hidden" name="rm" value="2" />*}
					{if $prefs.payment_paypal_ipn eq 'y'}
						<input type="hidden" name="notify_url" value="{$payment_info.paypal_ipn|escape}" />
					{/if}
					{tr}Pay with Paypal:{/tr} <input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif" alt="PayPal" title="{tr}Pay with Paypal{/tr}"/> 
				</form>
			{elseif $prefs.payment_system eq 'cclite' && $prefs.payment_cclite_gateway neq ''}
				{if (!empty($ccresult) or !empty($ccresult2)) and $ccresult_ok}
					<form action="{query _type='relative'}" method="post">
						<input type="hidden" name="invoice" value="{$payment_info.paymentRequestId|escape}" />
						<input type="hidden" name="cookietab" value="1" />
						<input type="submit" value="{tr}Refresh page{/tr}" />
					</form>
					{remarksbox title="{tr}Payment info{/tr}" type="info"}
						{$ccresult}<br />
						{$ccresult2}
					{/remarksbox}
				{else}
					<form action="{query _type='relative'}" method="post">
						<input type="hidden" name="invoice" value="{$payment_info.paymentRequestId|escape}" />
						<input type="hidden" name="cclite_payment_amount" value="{$payment_info.amount_remaining|escape}" />
						<input type="submit" value="{tr}Transfer currency now{/tr}" />
					</form>
					{if (!empty($ccresult) or !empty($ccresult2))}
						{remarksbox title="{tr}Payment problem{/tr}" type="info"}
							{$ccresult}<br />
							{$ccresult2}
						{/remarksbox}
					{/if}
				{/if}
			{/if}
			{if !empty($prefs.payment_manual)}
				{capture name=wp_payment_manual}wiki:{$prefs.payment_manual}{/capture}
				{include file=$smarty.capture.wp_payment_manual}
			{/if}
		{/if}
	</p>

	{if $payment_info.fullview && $payment_info.payments|@count}
		{if count($payment_info.payments) ne 1}<ol>{else}<ul>{/if}
			{foreach from=$payment_info.payments item=payment}
				<li>
					{if $payment.type eq 'user'}
						{include file=tiki-payment-user.tpl payment=$payment currency=$payment_info.currency}
					{elseif $payment.type eq 'paypal'}
						{include file=tiki-payment-paypal.tpl payment=$payment}
					{elseif $payment.type eq 'cclite'}
						{include file=tiki-payment-cclite.tpl payment=$payment}
					{/if}
				</li>
			{/foreach}
		{if count($payment_info.payments) ne 1}</ol>{else}</ul>{/if}
	{/if}

	{if $payment_info.state eq 'outstanding' || $payment_info.state eq 'overdue'}

		{permission type=payment object=$payment.paymentRequestId name=payment_manual}
			<form method="post" action="tiki-payment.php">
				<fieldset>
					<legend>{tr}Manual payment entry{/tr}</legend>

					<p><input type="text" name="manual_amount" class="right" />&nbsp;{$payment_info.currency|escape}</p>
					<p><label for="payment-note">{tr}Note{/tr}</label></p>
					<p><textarea id="payment-note" name="note" style="width: 98%;" rows="6"></textarea></p>
					<p><input type="submit" value="{tr}Enter payment{/tr}" /><input type="hidden" name="invoice" value="{$payment_info.paymentRequestId|escape}" /></p>
				</fieldset>
			</form>
		{/permission}
	{/if}
</div>
