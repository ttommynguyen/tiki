<div class="posts">
  <h2><a href="tiki-view_blog.php?blogId={$blog.blogId}">{$blog.title}</a></h2>
  {foreach from=$posts item=post}
    {$post.created|tiki_date_format:"%m/%d/%Y"} <a href="tiki-view_blog_post.php?blogId={$post.blogId}&postId={$post.postId}">{$post.title}</a><br />
  {/foreach}
</div>