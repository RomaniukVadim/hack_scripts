<?php
if (intval($vbulletin->versionnumber) == 3)
{
	if (strpos($hook_query_fields, 'post.pagetext AS description') === false)
	{
		$hook_query_fields .= ', post.pagetext AS description ';
	}

	if (strpos($hook_query_joins, 'LEFT JOIN " . TABLE_PREFIX . "post AS post ON(post.postid = thread.firstpostid)') === false)
	{
		$hook_query_joins .= " LEFT JOIN " . TABLE_PREFIX . "post AS post ON(post.postid = thread.firstpostid) ";
	}
}
?>