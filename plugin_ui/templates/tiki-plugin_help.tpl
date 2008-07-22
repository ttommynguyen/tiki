<h3>{$plugin.name|escape}</h3>
<div class="plugin-desc">
{$plugin.description}
</div>
<div class="plugin-sample">
&#123;{$plugin_name}(
{foreach key=name item=param from=$plugin.params}
	<div class="plugin-param">
	{if param.required}
		{$name}=&gt;<em>"{$param.description|escape}"</em>
	{else}
		[ {$name}=&gt;<em>"{$param.description|escape}"</em> ]
	{/if}
	</div>
{/foreach}
)&#125;
<div class="plugin-param">
{$plugin.body}
</div>
&#123;{$plugin_name}&#125;
</div>
