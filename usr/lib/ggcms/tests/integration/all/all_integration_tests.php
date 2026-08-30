<?php
	use PHPUnit\Framework\TestCase;

	require('/var/www/ggcms_install_directories.php');
	require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
	
	depreq('vendor/autoload.php');
	
	use GuzzleHttp\Client;
	require('/usr/lib/ggcms/tests/traits/Files/TestFile.php');
	testreq('traits/Errors/MissingDatabase.php');
	
	testreq('traits/Redirects/CopyPasteErrorRedirect.php');
	testreq('traits/Redirects/GitRedirect.php');
	testreq('traits/Redirects/ImageDirectoryRedirect.php');
	testreq('traits/Redirects/LinuxRedirect.php');
	testreq('traits/Redirects/MailTo.php');
	testreq('traits/Redirects/MultipleSlashesInURL.php');
	testreq('traits/Redirects/TypoErrorRedirect.php');
	testreq('traits/Redirects/UndesirableParametersRedirect.php');
	
	$domain = 'clonefrom.com';
	load_config('error/errormessage_globals.php', $domain);
	
	ggreq('classes/Development/Version.php');
	
	class AllIntegrationTests extends TestCase {
		use MissingDatabaseTrait;
		
		use CopyPasteErrorRedirectTrait;
		use ImageDirectoryRedirectTrait;
		use GitRedirectTrait;
		use LinuxRedirectTrait;
		use MailToTrait;
		use MultipleSlashesInURLTrait;
		use TypoErrorRedirectTrait;
		use UndesirableParametersRedirect;
	}
?>