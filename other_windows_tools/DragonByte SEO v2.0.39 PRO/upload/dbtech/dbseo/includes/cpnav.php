<?php
construct_nav_option($vbphrase['dbtech_dbseo_ga_reports'],					'index.php?do=analytics');
construct_nav_option($vbphrase['dbtech_dbseo_gwt_reports'],					'index.php?do=webmaster');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_settings')) ? eval($hook) : false;
construct_nav_group($vbphrase['dbtech_dbseo_seo_reports']);

construct_nav_option($vbphrase['dbtech_dbseo_general_settings'],			'index.php?do=settings&dogroup=general');
construct_nav_option($vbphrase['dbtech_dbseo_tagging_settings'],			'index.php?do=settings&dogroup=tagging');
construct_nav_option($vbphrase['dbtech_dbseo_google_analytics'],			'index.php?do=settings&dogroup=analytics');
construct_nav_option($vbphrase['dbtech_dbseo_dynamicmeta'],					'index.php?do=settings&dogroup=dynamicmeta');
construct_nav_option($vbphrase['dbtech_dbseo_externallink'],				'index.php?do=settings&dogroup=external');
construct_nav_option($vbphrase['dbtech_dbseo_stopwords'],					'index.php?do=settings&dogroup=stopwords');
construct_nav_option($vbphrase['dbtech_dbseo_keywords'],					'index.php?do=keywords');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_settings')) ? eval($hook) : false;
construct_nav_group($vbphrase['dbtech_dbseo_seo_settings']);

construct_nav_option($vbphrase['dbtech_dbseo_urlsettings'],					'index.php?do=settings&dogroup=urlrewrite_general');
construct_nav_option($vbphrase['dbtech_dbseo_forumurl'],					'index.php?do=settings&dogroup=urlrewrite_forum');
if (intval($vbulletin->versionnumber) == 4)
{
construct_nav_option($vbphrase['dbtech_dbseo_cmsurl'],						'index.php?do=settings&dogroup=urlrewrite_cms');
}
construct_nav_option($vbphrase['dbtech_dbseo_blogurl'],						'index.php?do=settings&dogroup=urlrewrite_blog');
construct_nav_option($vbphrase['dbtech_dbseo_socialgroupurl'],				'index.php?do=settings&dogroup=urlrewrite_socialgroup');
construct_nav_option($vbphrase['dbtech_dbseo_memberprofileurl'],			'index.php?do=settings&dogroup=urlrewrite_memberprofile');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_urlrewrite')) ? eval($hook) : false;
construct_nav_group($vbphrase['dbtech_dbseo_url_rewrite_settings']);

construct_nav_option($vbphrase['dbtech_dbseo_general_settings'],			'index.php?do=settings&dogroup=sitemap_general');
construct_nav_option($vbphrase['dbtech_dbseo_page_settings'],				'index.php?do=settings&dogroup=sitemap_page');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_sitemap')) ? eval($hook) : false;
/*DBTECH_PRO_START*/
construct_nav_option($vbphrase['dbtech_dbseo_custom_sitemap_urls'],			'index.php?do=sitemapurls');
/*DBTECH_PRO_END*/
construct_nav_option($vbphrase['dbtech_dbseo_build_sitemap'],				'index.php?do=buildsitemap');
construct_nav_group($vbphrase['dbtech_dbseo_sitemap_settings']);

construct_nav_option($vbphrase['dbtech_dbseo_general_settings'],			'index.php?do=settings&dogroup=socialshare_general');
construct_nav_option($vbphrase['dbtech_dbseo_page_settings'],				'index.php?do=settings&dogroup=socialshare_page');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_socialshare')) ? eval($hook) : false;
construct_nav_group($vbphrase['dbtech_dbseo_socialshare_settings']);

($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_settinggroup')) ? eval($hook) : false;

/*DBTECH_PRO_START*/
construct_nav_option($vbphrase['dbtech_dbseo_spider_log'],					'index.php?do=spiderlog');
construct_nav_option($vbphrase['dbtech_dbseo_sitemap_log'],					'index.php?do=sitemaplog');
construct_nav_option($vbphrase['dbtech_dbseo_sitemap_build_log'],			'index.php?do=sitemapbuildlog');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_logs')) ? eval($hook) : false;
construct_nav_group($vbphrase['dbtech_dbseo_logs']);
/*DBTECH_PRO_END*/

construct_nav_option($vbphrase['dbtech_dbseo_system_info'],					'index.php?do=info');
construct_nav_option($vbphrase['dbtech_dbseo_manage_content_tags'],			'index.php?do=tagging');
/*DBTECH_PRO_START*/
construct_nav_option($vbphrase['dbtech_dbseo_vbseo_import'],				'index.php?do=vbseoimport');
construct_nav_option($vbphrase['dbtech_dbseo_reset_settings'],				'index.php?do=reset');
construct_nav_option($vbphrase['dbtech_dbseo_backup_restore'],				'index.php?do=impex');
/*DBTECH_PRO_END*/
construct_nav_option($vbphrase['dbtech_dbseo_clear_cache'],					'index.php?do=clearcache');
//construct_nav_option($vbphrase['dbtech_dbseo_repair_cache'],				'index.php?do=repaircache');
($hook = vBulletinHook::fetch_hook('dbtech_dbseo_cpnav_maintenance')) ? eval($hook) : false;
construct_nav_group($vbphrase['maintenance']);