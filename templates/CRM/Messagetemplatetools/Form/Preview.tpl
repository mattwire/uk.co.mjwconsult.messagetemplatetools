
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
</div>
<div class="crm-block crm-form-block">
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{if $tokens}
  <div class="crm-section collapsed">
    <div class="crm-accordion-header">{ts}Subject Tokens{/ts}</div>
    <div class="crm-accordion-body collapsed">
      <h3>Tokens</h3>
      {foreach from=$tokens.tokens.subject key=k item=value}
        {foreach from=$value item=v2}
          {$k}.{$v2}<br />
        {/foreach}
      {/foreach}
      <h3>Smarty</h3>
      {foreach from=$tokens.smarty.subject item=value}
        {$value}<br />
      {/foreach}
    </div>
  </div>
  <div class="crm-section collapsed">
    <div class="crm-accordion-header">{ts}HTML Tokens{/ts}</div>
    <div class="crm-accordion-body">
      <h3>Tokens</h3>
      {foreach from=$tokens.tokens.html key=k item=value}
        {foreach from=$value item=v2}
          {$k}.{$v2}<br />
        {/foreach}
      {/foreach}
      <h3>Smarty</h3>
      {foreach from=$tokenElements item=value}
        {$form.$value.label} {$form.$value.html}<br />
      {/foreach}
    </div>
  </div>
  <div class="crm-section collapsed">
    <div class="crm-accordion-header">{ts}Text Tokens{/ts}</div>
    <div class="crm-accordion-body collapsed">
      <h3>Tokens</h3>
      {foreach from=$tokens.tokens.text key=k item=value}
        {foreach from=$value item=v2}
          {$k}.{$v2}<br />
        {/foreach}
      {/foreach}
      <h3>Smarty</h3>
      {foreach from=$tokens.smarty.text item=value}
        {$value}<br />
      {/foreach}
    </div>
  </div>
{/if}

{if $renderedMail}
  <div class="crm-section">
    <div class="crm-accordion-header">{ts}Subject{/ts}</div>
    <div class="crm-accordion-body">{$renderedMail.subject}</div>
  </div>
  <div class="crm-section">
    <div class="crm-accordion-header">{ts}HTML version{/ts}</div>
    <div class="crm-accordion-body">{$renderedMail.html}</div>
  </div>
  <div class="crm-section">
    <div class="crm-accordion-header">{ts}Text version{/ts}</div>
    <div class="crm-accordion-body">{$renderedMail.text}</div>
  </div>
{/if}

</div>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
