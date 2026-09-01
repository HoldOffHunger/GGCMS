<?php

			# FIXME: TODO: Add -> Directly Excluded URI's, like the commented out ones in RepoURLs

	class IssueLogging {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function controlScripts() {		// if you are not logged in, yeah, then you can't access these, and we don't care too much about the resulting redirects (if there are errors, we care about them, but ErrorLogging.php takes care of that
			return [
				'modify',
				'transfer',
			];
		}
		
		public function ignorableScriptsWithExtensions() {	// anything like "blah/blah/login.css" will be not logged
			return [
				'login.css',
				'admin.js',
			];
		}
		
		public function prohibitedURLs() {
			return [
				'/b0',
				'/404error-test-page-by-turbo-website-reviewer',
			
				'/7/',
				'/2/',
			
				'/3/',
				'/3.1/',
				'/3.3/',
				'/3.4/',
				'/3.5/',
				'/3.6/',
				'/3.7/',
				'/3.8/',
				'/3.9/',
			
				'/4/',
				'/4.0/',
				'/4.1/',
				'/4.2/',
				'/4.3/',
				'/4.4/',
				'/4.5/',
				'/4.6/',
				'/4.7/',
				'/4.8/',
				'/4.9/',
			
				'/5/',
				'/5.0/',
				'/5.1/',
				'/5.2/',
				'/5.3/',
				'/5.4/',
				'/5.5/',
				'/5.6/',
				'/5.7/',
				'/5.8/',
				'/5.9/',
				'/5.10/',
				'/5.11/',
			
				'/6/',
				'/6.0/',
				'/6.1/',
				'/6.2/',
				'/6.3/',
				'/6.4/',
				'/6.5/',
				'/6.6/',
				'/6.7/',
				'/6.8/',
				'/6.9/',
				'/6.10/',
				
				'/7/',
				'/7.5.1804/',
				'/7.3.1611/',
				'/7.2.1511/',
				'/7.4.1708/',
				'/7.0.1406/',
				'/7.1.1503/',
				
				'/adminer',
				'/adminer.sql',
				'/adminer.sql.gz',
				
				'/adminer/',
				'/mysqladmin',
				
				'/admin',
				'/admin2',	# you know! like, uh, our spare admin login page!
				'/admins',
				
				'/xxxss',
				'/new-site/',
				'/recovery',
				'/old',
				'/old/',
				'/blog',
				'/homepage.html',
				'/test/',
				'/backup/',
				'/backup',
				'/dev/',
				'/css/album.css',
				'/js/common.js',
				'/home/',
				'/main/',
				'/cms/',
				'/temp/',
				'/demo/',
				'/test',
				'/2018/',
				'/2019/',
				'/2020/',
				'/tmp/',
				'/v1/',
				'/v2/',
				'/js/comm.js',
				'/wp-sitemap.xml',
				'/wp',
			];
		}
		
			# FIXME: Do this only for marked domains
		public function prohibitedURLStarts_RepoURLs() {	// we are not a Linux repository
			return [
				'/6.10/os/',
				'/6.10/virt/',
				'/6.10/contrib/',
				'/6.0/0_Readme',
				'/6.1/0_Readme',
				'/6.2/0_Readme',
				'/6.3/0_Readme',
				'/6.8/centosplus/',
				'/6.0/readme',
				'/6.1/readme',
				'/6.2/readme',
				'/6.3/readme',
				'/6.4/readme',
				'/6.5/readme',
				'/6.6/readme',
				'/6.7/readme',
				'/6.8/readme',
				'/6.9/readme',
				'/6.0/?C=',
				'/6.1/?C=',
				'/6.2/?C=',
				'/6.3/?C=',
				'/6.4/?C=',
				'/6.5/?C=',
				'/6.6/?C=',
				'/6.7/?C=',
				'/6.8/?C=',
				'/6.9/?C=',
				'/6.10/?C=',
				'/6/?C=',
				'/6.8/contrib/',
				'/6.8/cr/',
				'/6.8/extras/',
				'/6.8/fasttrack/',
				'/6.8/isos/',
				'/6.8/virt/',
				'/6.8/os/',
				'/6.8/updates/',
				'/6.9/centosplus/',
				'/6.9/storage/',
				'/6.9/contrib/',
				'/6.9/virt/',
				'/6.10/sclo/',
				'/6.9/isos/',
				'/6/sclo/',
				'/6.9/cr/',
				'/6.9/extras/',
				'/6/isos/',
				'/6.9/fasttrack/',
				'/6.9/os/',
				'/6.9/updates/',
				'/6/centosplus/',
				'/6/contrib/',
				'/6/cr/',
				'/6/virt/',
				'/6/extras/',
				'/6.10/cr/',
				'/6.10/updates/',
				'/6/storage/',
				'/6.10/cloud/',
				'/6.10/fasttrack/',
				'/6.10/centosplus/',
				'/6.8/cloud/',
				'/6/fasttrack/',
				'/6.7/os/',
				'/6.9/sclo/',
				'/6.10/storage/',
				'/6.8/storage/',
				'/6/os/',
				'/6.9/cloud/',
				'/6/updates/',
				'/6.10/extras/',
				'/6/cloud/',
				'/6.10/isos/',
				'/7.4.1708/os/',
				'/7.3.1611/fasttrack/',
				'/7.5.1804/extras/',
				'/7.3.1611/isos/',
				'/7.3.1611/centosplus/',
				'/7.3.1611/rt/',
				'/7.5.1804/paas/',
				'/7.4.1708/opstools/',
				'/7/atomic/',
				'/7.3.1611/atomic/',
				'/7.3.1611/paas/',
				'/7.4.1708/centosplus/',
				'/7.3.1611/sclo/',
				'/7.5.1804/updates/',
				'/7.4.1708/nfv/',
				'/7.5.1804/opstools/',
				'/7.5.1804/dotnet/',
				'/7.5.1804/centosplus/',
				'/7.5.1804/configmanagement/',
				'/7.5.1804/storage/',
				'/7.3.1611/virt/',
				'/7.5.1804/rt/',
				'/7.5.1804/sclo/',
				'/7.5.1804/virt/',
				'/7.3.1611/os/',
				'/7.5.1804/isos/',
				'/7.4.1708/storage/',
				'/7.3.1611/opstools/',
				'/7.4.1708/sclo/',
				'/7.3.1611/storage/',
				'/7.5.1804/cloud/',
				'/7.5.1804/cr/',
				'/7.4.1708/dotnet/',
				'/7.4.1708/extras/',
				'/7.5.1804/atomic/',
				'/7.4.1708/isos/',
				'/7.5.1804/fasttrack/',
				'/7.4.1708/cr/',
				'/7.4.1708/cloud/',
				'/7.3.1611/extras/',
				'/7.4.1708/atomic/',
				'/7.4.1708/rt/',
				'/7.4.1708/fasttrack/',
				'/7.5.1804/os/',
				'/7.3.1611/cloud/',
				'/7.4.1708/updates/',
				'/7.3.1611/updates/',
				'/7.4.1708/paas/',
				'/7.4.1708/virt/',
				'/5.11/addons/',
				'/5.11/fasttrack/',
				'/5.11/os/',
				'/5/os/',
				'/5/contrib/',
				'/5.11/cr/',
				'/5/extras/',
				'/5/centosplus/',
				'/5/addons/',
				'/5/updates/',
				'/5.11/centosplus/',
				'/5.11/contrib/',
				'/5.11/isos/',
				'/5/isos/',
				'/5.11/extras/',
				'/5/fasttrack/',
				'/5.11/updates/',
				'/5/cr/',
				'/5.11/readme',
				
				'/7.4.1708/?C=',
				'/7.0.1406/?C=',
				'/7.1.1503/?C=',
				'/7.2.1511/?C=',
				'/7.5.1804/?C=',
				'/7.3.1611/?C=',
				
				'/7.4.1708/readme',
				'/7.0.1406/readme',
				'/7.2.1511/readme',
				'/7.1.1503/readme',
				'/7.3.1611/readme',
				'/7.0.1406/readme',
				'/7.4.1708/readme',
				
				'/5/?C=',
				'/5.0/?C=',
				'/5.1/?C=',
				'/5.2/?C=',
				'/5.3/?C=',
				'/5.4/?C=',
				'/5.5/?C=',
				'/5.6/?C=',
				'/5.7/?C=',
				'/5.8/?C=',
				'/5.9/?C=',
				'/5.11/?C=',
				
				'/5/readme',
				'/5.0/readme',
				'/5.1/readme',
				'/5.2/readme',
				'/5.3/readme',
				'/5.4/readme',
				'/5.5/readme',
				'/5.6/readme',
				'/5.7/readme',
				'/5.8/readme',
				'/5.9/readme',
				'/5.10/readme',
				
				'/4/?C=',
				'/4.0/?C=',
				'/4.1/?C=',
				'/4.2/?C=',
				'/4.3/?C=',
				'/4.4/?C=',
				'/4.5/?C=',
				'/4.6/?C=',
				'/4.7/?C=',
				'/4.8/?C=',
				'/4.9/?C=',
				'/4.10/?C=',
				
				'/4.2/HEADER.html',
				'/4.3/HEADER.html',
				
				'/4/readme',
				'/4.0/readme',
				'/4.1/readme',
				'/4.2/readme',
				'/4.3/readme',
				'/4.4/readme',
				'/4.5/readme',
				'/4.6/readme',
				'/4.7/readme',
				'/4.8/readme',
				'/4.9/readme',
				
				'/3/?C=',
				'/3.1/?C=',
				'/3.3/?C=',
				'/3.4/?C=',
				'/3.5/?C=',
				'/3.6/?C=',
				'/3.7/?C=',
				'/3.8/?C=',
				'/3.9/?C=',
				
				'/2/?C=',
				'/2/license.txt',
				'/2/centos2-scripts-v1.tar',
				
				'/1/license.txt',
				
				'/7/?C=',
				'/7/sclo/',
				'/7/atomic/',
				'/7/paas/',
				'/7/isos/',
				'/7/fasttrack/',
				'/7/storage/',
				'/7/virt/',
				'/7/updates/',
				'/7/centosplus/',
				'/7/opstools/',
				'/7/dotnet/',
				'/7/os/',
				'/7/cloud/',
				'/7/cr/',
				'/7/rt/',
				'/7/extras/',
				'/7/nfv/',
			];
		}
		
		public function prohibitedURLStarts_WordPressURLs() {	// we are not WordPress
			return [
				'/.../',
				'/wp-config.txt',
				'/wp-config.old',
				'/wp-config.inc',
				'/wp1/wp-includes/',
				'/shop/wp-includes/',
				'/2019/wp-includes/',
				'/2018/wp-includes/',
				'/2020/wp-includes/',
				'/2021/wp-includes/',
				'/news/wp-includes/',
				'/wp/wp-includes/',
				'/old-wp/',
				'/wp-old',
				'/website/wp-includes/',
				'/wordpress/wp-includes/',
				'/web/wp-includes/',
				'/blog/wp-includes/',
				'/bak/wp-admin/',
				'/wp-includes',
				'/sito/wp-includes/',
				'/cms/wp-includes/',
				'/site/wp-includes/',
				'/wp2/wp-includes/',
				'/media/wp-includes/',
				'/test/wp-includes/',
				'/news/wp-admin/',
				'/backup/wp-admin/',
				'/bkp/wp-admin/',
				'/blog/wp-admin/',
				'/dev/wp-admin/',
				'/new/wp-admin/',
				'/oldsite/wp-admin/',
				'/Oldsite/wp-admin/',
				'/old/wp-admin/',
				'/Old/wp-admin/',
				'/staging/wp-admin/',
				'/test/wp-admin/',
				'/wordpress',
				'/wp/',
				'/wp-admin/',
				'/wp-content',
				'/wp-json/',
				'/wp/wp-admin/',
				'/blog/wp-content/',
				'/blog/wp-json/',
				'/wp1/',
				'/wp2/',
			];
		}
		
		public function prohibitedURLStarts_AdminURLs() {	// we have only one admin script, and these are not it
			return [
				'/HEADER.images/',
				'/3/admin/',
				'/7/configmanagement/',
				'/a/admin/',
				'/aaa/admin/',
				'/abc/admin/',
				'/about/admin/',
				'/academic/admin/',
				'/access/admin/',
				'/accessgranted/admin/',
				'/account/admin/',
				'/action/admin/',
				'/actions/admin/',
				'/adm/admin/',
				'/administration/admin/',
				'/adminlogon/',
				'/mysqladmin/',
				'/python-admin/',
				'/adminsql/admin/',
				'/admon/admin/',
				'/app/admin/',
				'/applet/admin/',
				'/applets/admin/',
				'/appliance/admin/',
				'/applications/admin/',
				'/apply/admin/',
				'/author/admin/',
				'/catalog/Admin',
				'/Admin/',
				'/admin/',
				'/aadmin/',
				'/Admin/Images/',
				'/admin/login/',
				'/admin/themes/',
				'/admin_login/',
				'/admin_118/Admincenter/',
				'/administrator',
				'/adminlogin/',
				'/adminsoft/',
				'/all/admin/',
				'/alpha/admin/',
				'/auth/',
				'/admin_temp/',
				'/blog/administrator/',
				'/bakup/admin/',
				'/admin1/.env',
				'/administration/vendor/phpunit/',
				'/common/admin/',
				'/data/admin/',				
				'/adsl/admin/',
				'/agent/admin/',
				'/Public/Admin/Images/',
				'/public/admin/',
				'/agents/admin/',
				'/analog/admin/',
				'/analyse/admin/',
				'/announcements/admin/',
				'/answer/admin/',
				'/api/admin/',
				'/e/admin/',
				'/oc/admin/',
				'/statics/js/',
				'/static/admin/',
				'/templates/admin/',
				'/sys/Admincenter/',
				'/admsys/Admincenter/',
				'/mmm/Admincenter/',
				'/aaa/Admincenter/',
				'/www/Admincenter/',
				'/install/index.php',
				'/UserCenter/',
				'/myadmin',			# no closing slash
				'/webadmin/',
				'/sss@333/Admincenter/',
				'/adm/Admincenter/',
				'/a/Admincenter/',
				'/admins/',
				'/siteadmin',
				'/public/admin.php/',
				'/wwwroot/Admincenter/',
				'/.well-known/',
			];
		}
		
		public function prohibitedURLStarts_InferiorCMSURLs() {	// we have only one admin script, and these are not it
			return [
				'/old-site/',
				'/bak/',
				'/_ignition/',
				'/bk/',
				'/admin_aspcms/',
				'/android/',
				'/bitrix/admin/',
				'/cscpLoginWeb/scripts/',
				'/DesktopModules/',
				'/ids/admin/',
				'/jeeadmin/jeecms/',
				'/jeeadmin/jeecms/index.do',
				'/login/jeecms.do',
				'/magento/',
				'/phpmyadmin',		# no closing paren
				'/static/admincp/',
				'/system/skins/',
				'/Telerik.Web.UI.WebResource',
				'/Template/Default/Skin/',
				'/tpl/login/user/',
				'/dede/img/',
				'/uc_server/control/admin/',
				'/whir_system/',
				'/ht/Admincenter/',
				'/joomla/administrator/',
				'/houtai/Admincenter/',
				'/admindede',
				'/tags/',
				'/html-templates/',
				'/e/data/js/',
				'/yule.js',
				'/vendor/phpunit/',
				'/public/js/',
			];
		}
		
		public function prohibitedURLStarts_MiscellaneousURLs() {	// various URLs to skip logging on
			return [
				'/404.jpg',
				'/apache/',
				'/app/images/login/',
				'/apps/admin/_static/image/',
				'/assets/',
				'/Components/General/',
				'/images/login/',
				'/Images/login/',
				'/images/login9/',
				'/img/pic/login/',
				'/login/.env',
				'/css/images/',
				'/.env',
				'/public/.env',
				'/blog/.env',
				'/member/templets/',
				'/nobody/mobile.htm',
				'/public/simpleboot/',
				'/site/',
				'/static/home/',
				'/template/css/',
				'/user/',
				'/web/',
				'/web2/',
				'/web.public/',
			];
		}
		
		public function prohibitedURLStarts() {		# URLS may NOT start with any of these
			return array_merge(
				$this->prohibitedURLStarts_RepoURLs(),
				$this->prohibitedURLStarts_WordPressURLs(),
				$this->prohibitedURLStarts_AdminURLs(),
				$this->prohibitedURLStarts_InferiorCMSURLs(),
				$this->prohibitedURLStarts_MiscellaneousURLs(),
			);
		}
		
		public function prohibitedURLEnds() {		# URLS may NOT end with any of these
			return [
				'/admin',
				'/admin/',
				'/admin/images/',
				'/admin/resources/images/',
				'/admin/resources/css/',
				'/admin/flags/',
				'/admin/resources/images/black/menu/menu.gif',
				'/admin/resources/images/default/button/btn-arrow.gif',
				'/admin/resources/images/black/button/btn-arrow.gif',
				'/admin/resources/images/default/menu/menu.gif',
				'/html-templates/base-template.html',
				'/wlwmanifest.xml',
			];
		}
		
		function prohibitedURLEndStructure() {
		        $prohibited_urls = $this->prohibitedURLEnds();
		        
		        $structure = [];
		        
		        foreach($prohibited_urls as $prohibited_url) {
				$length = strlen($prohibited_url);
				
				if(!$structure[$length]) {
					$structure[$length] = [];
				}
				
				$structure[$length][] = mb_strtolower($prohibited_url);
		        }
		        
		        return $structure;
		}
		
		public function ignorableScripts() {	# any script, like blah/blah/admin.php, or blah/admin.json, will not be logged
			return [
				'xmlrpc',
				'wp-info',
				'wp-load',
				'wp-login_bak',
				'wp-1ogin_bak',	#not a typo
				'wp-class',
				'm_tixian',
				'403',
				'404',
				'4o3',
				'4o4',
				'ads',
				'app-ads',
				'admin',
				'admins',
				'adminpanel',
				'administrator',
				'_adminer',
				'adminer',
				'er-4.2.2',
				'adminer-3.5.1',
				'adminer-3.6.3',
				'adminer-3.7.1',
				'adminer-4.1.0',
				'adminer-4.1.0-mysql',
				'adminer-4.2.0',
				'adminer-4.2.1',
				'adminer-4.2.3',
				'adminer-4.2.4',
				'adminer-4.2.4-mysql',
				'adminer-4.2.5',
				'adminer-4.2.5-mysql',
				'adminer-4.3.0',
				'adminer-4.3.0-mysql',
				'adminer-4.3.1',
				'adminer-4.3.1-mysql',
				'adminer-4.4.0',
				'adminer-4.4.0-mysql',
				'adminer-4.5.0',
				'adminer-4.5.0-mysql',
				'adminer-4.6.0',
				'adminer-4.6.0-mysql',
				'adminer-4.6.1',
				'adminer-4.6.2',
				'adminer-4.6.2-mysql',
				'adminer-4.6.3',
				'adminer-4.7.0',
				'adminer-4.7.1',
				'adminer-4.7.2',
				'adminer-4.7.4',
				'adminer-4.7.5',
				'admins',
				'admin_login',
				'commentList',
				'config_update',
				'eval-stdin',
				'kefuxian',
				'Login',
				'log',
				'wp-booking',
				'login_admin',
				'login_index',
				'login_site',
				'login.action',
				'Manage_Admin',
				'maintlogin',
				'media-admin',
				'mobilelogin',
				'mysql-adminer',
				'not_login',
				'phpminiadmin',
				'public',
				'radminpass',
				'User_Login',
				'UserLogin',
				'webadmin',
				'wlwmanifest',
				'wp-old-index',
				'bitKay',
				'internal',
			];
		}
		
		public function excludedPatterns() {	# any url with an excluded pattern will not be logged
			return [
				'UNION SELECT CHAR(45,120,49,45,81,45)',
				'concat(user,0x3a,password)',
				'CONCAT(0x7c,userid,0x7c,pwd)',
				'username+CHR(124)+password',
				'153,10,null,null,126,174,null,null,-5,null,null,false,false,null,null,',
			];
		}
		
		function prohibitedURLStartStructure() {
		        $prohibited_urls = $this->prohibitedURLStarts();
		        
		        $structure = [];
		        
		        foreach($prohibited_urls as $prohibited_url) {
				$length = strlen($prohibited_url);
				
				if(!$structure[$length]) {
					$structure[$length] = [];
				}
				
				$structure[$length][] = mb_strtolower($prohibited_url);
		        }
		        
		        return $structure;
		}
		
		public function isURLStartProhibited() {
			$url = mb_strtolower($_SERVER['REQUEST_URI']);
			
			$url_length = strlen($url);
			
			$prohibited_urls = $this->prohibitedURLStartStructure();
			$prohibited_url_keys = array_keys($prohibited_urls);
			$prohibited_url_keys_count = count($prohibited_url_keys);
			
			for($i = 0; $i < $prohibited_url_keys_count; $i++) {
				$prohibited_url_key = $prohibited_url_keys[$i];
				
				if($url_length >= $prohibited_url_key) {
					$prohibited_url = $prohibited_urls[$prohibited_url_key];
					$prohibited_url_count = count($prohibited_url);
					for($j = 0; $j < $prohibited_url_count; $j++) {
						$prohibited_url_item = $prohibited_url[$j];
					
						if(substr($url, 0, $prohibited_url_key) === $prohibited_url_item) {
					#	if(str_starts_with($url, $prohibited_url_item)) {
							return TRUE;
						}
					}
				}
			}
			
			return FALSE;
		}
		
		public function isURLEndProhibited() {
			$url = mb_strtolower($_SERVER['REQUEST_URI']);
			
			$url_length = strlen($url);
			
			$prohibited_urls = $this->prohibitedURLEndStructure();
			$prohibited_url_keys = array_keys($prohibited_urls);
			$prohibited_url_keys_count = count($prohibited_url_keys);
			
			for($i = 0; $i < $prohibited_url_keys_count; $i++) {
				$prohibited_url_key = $prohibited_url_keys[$i];
				
				if($url_length >= $prohibited_url_key) {
					$prohibited_url = $prohibited_urls[$prohibited_url_key];
					$prohibited_url_count = count($prohibited_url);
					for($j = 0; $j < $prohibited_url_count; $j++) {
						$prohibited_url_item = $prohibited_url[$j];
					
						if(substr($url, 0 - $prohibited_url_key) === $prohibited_url_item) {
					#	if(str_starts_with($url, $prohibited_url_item)) {
							return TRUE;
						}
					}
				}
			}
			
			return FALSE;
		}
		
		public function isScriptIgnorable() {
			$control_scripts = $this->controlScriptsStructure();
			
			if($control_scripts[$this->handler->script_file] && !$this->handler->script->script->canUserAccess()) {
				return TRUE;
			}
			
			$ignorable_scripts = $this->ignorableScriptsStructure();
			if($ignorable_scripts[$this->handler->script_file]) {
				return TRUE;
			}
			
			$ignorable_scripts = $this->ignorableScriptsWithExtensionsStructure();
			
			if($ignorable_scripts[$this->handler->desired_script]) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function prohibitedURLsStructure() {
		        $prohibited_urls = $this->prohibitedURLs();
		        
		        $structure = [];
		        
		        foreach($prohibited_urls as $prohibited_url) {
		        	$structure[$prohibited_url] = TRUE;
		        }
		        
		        return $structure;
		}
		
		public function controlScriptsStructure() {
		        $control_scripts = $this->controlScripts();
		        
		        $structure = [];
		        
		        foreach($control_scripts as $control_script) {
		        	$structure[$control_script] = TRUE;
		        }
		        
		        return $structure;
		}
		
		public function ignorableScriptsWithExtensionsStructure() {
		        $ignorable_scripts = $this->ignorableScriptsWithExtensions();
		        
		        $structure = [];
		        
		        foreach($ignorable_scripts as $ignorable_script) {
		        	$structure[$ignorable_script] = TRUE;
		        }
		        
		        return $structure;
		}
		
		function ignorableScriptsStructure() {
		        $ignorable_scripts = $this->ignorableScripts();
		        
		        $structure = [];
		        
		        foreach($ignorable_scripts as $ignorable_script) {
		        	$structure[$ignorable_script] = TRUE;
		        }
		        
		        return $structure;
		}
		
		public function isURLExcluded() {
			$url = mb_strtolower(urldecode($_SERVER['REQUEST_URI']));
			$excluded_patterns = $this->excludedPatterns();
			$excluded_patterns_count = count($excluded_patterns);
			
			for($i = 0; $i < $excluded_patterns_count; $i++) {
				$excluded_pattern = mb_strtolower($excluded_patterns[$i]);
				if(strpos($url, $excluded_pattern) !== FALSE) {
					return TRUE;
				}
			}
			
			return FALSE;
		}
		
		public function excludedActions() {		# BT: FIX abstract out
			return [
				'dispMemberLoginForm'=>TRUE,
			];
		}
		
		public function isActionIgnorable() {
			$excluded_actions = $this->excludedActions();
			
			if($excluded_actions[$this->handler->desired_action] || $excluded_actions[$_GET['act']]) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function areWeRedirectingToHTTPS() {
			if($_SERVER['HTTPS'] !== 'on' && $this->handler->script && $this->handler->script->script && $this->handler->script->script->isSecure()) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function isURLProhibited() {
			$prohibited_urls = $this->prohibitedURLsStructure();
			
			if($prohibited_urls[$_SERVER['REQUEST_URI']]) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		/*
			One oddity is one ticket.  Every 404 on a site carries the description
			'404 URL', so they collapse to a single counted ticket and the URLs that
			produced them live in InternalServerIssueInstance -- which is the whole
			point, since 404s from crawlers were most of this table.
		*/

		public function IssueSignature($args) {
			$signature_parts = [
				$args['script'],
				$args['issuetype'],
				$args['description'],
			];

			return hash('sha256', implode("\n", $signature_parts));
		}

		public function IssueScript() {
			if(!$this->handler) {
				return '';
			}

			if(!property_exists($this->handler, 'script_file')) {
				return '';
			}

			return (string)$this->handler->script_file;
		}
		
		public function createLog($args) {
			if($this->areWeRedirectingToHTTPS()) {
				return FALSE;
			}
			
			if($this->isURLProhibited()) {
				return FALSE;
			}
			
			if($this->isURLStartProhibited()) {
				return FALSE;
			}
			
			if($this->isURLEndProhibited()) {
				return FALSE;
			}
			
			if($this->isURLExcluded()) {
				return FALSE;
			}
			
			if($this->isScriptIgnorable()) {
				return FALSE;
			}
			
			if($this->isActionIgnorable()) {
				return FALSE;
			}
			
			$issue_script = $this->IssueScript();

			$internal_server_issue_insert_args = [
				'type'=>'InternalServerIssue',
				'instancetype'=>'InternalServerIssueInstance',
				'instancefield'=>'Issueid',
				'url'=>urldecode($_SERVER['REQUEST_URI']),
				'definition'=>[
					'Signature'=>$this->IssueSignature([
						'script'=>$issue_script,
						'issuetype'=>$args['issuetype'],
						'description'=>$args['description'],
					]),
					'Script'=>$issue_script,
					'IncidentCount'=>1,
					'IssueType'=>$args['issuetype'],
					'URL'=>urldecode($_SERVER['REQUEST_URI']),
					'Description'=>$args['description'],
					'Resolved'=>0,
					'ServerVariable'=>print_r($_SERVER, TRUE),
					'PostVariable'=>print_r($_POST, TRUE),
					'GetVariable'=>print_r($_GET, TRUE),
				],
			];
			#print("<PRE>");
			#print_r($internal_server_issue_insert_args);
			$this->internal_server_issue = $this->handler->db_access->CreateCountedRecord($internal_server_issue_insert_args);
			
			#print_r($this->internal_server_issue);
			
			return TRUE;
		}
	}

?>