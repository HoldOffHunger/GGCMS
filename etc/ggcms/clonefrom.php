<?php

		// https://console.developers.google.com/apis/credentials

	class defaultglobals {
						// Constructor
						// -------------------------------------------------------------------
						
		public function __construct($args) {
			$this->primaryhost = $this->PrimaryHostInfo();
			$this->mainmenu = $this->MainMenu();
			$this->styling = $this->Styling();
			$this->passwordseed = $this->NewRandomPasswordSeed();
			$this->apidata = $this->SetAPIData();
			$this->functionality = [
				'sharing'=>$this->SharingInfo(),
			];
			
			return $this;
		}
		
						// Primary Host Info
						// -------------------------------------------------------------------
		
		public function isProductionSite() {
			return FALSE;
		}
		
						// Primary Host Info
						// -------------------------------------------------------------------
		
		public function PrimaryHostInfo() {
			return [
				'id'=>0,
			];
		}
		
						// DB Info
						// -------------------------------------------------------------------
		
		public function NameServers() {
			return [
				'ns1.digitalocean.com',
				'ns2.digitalocean.com',
				'ns3.digitalocean.com',
			];
		}
		
		public function IPv4Addresses() {
			return [
				'YOUR IPv4 ADDRESSES HERE',
			];
		}
		
		public function IPv6Addresses() {
			return [
				'YOUR IPv6 ADDRESSES HERE',
			];
		}
		
		public function WebHostServices() {
			return [
				'Your Web Hosting Services Here',
			];
		}
		
		public function OverrideDatabaseName() {
			return FALSE;
		}
		
		public function DictionaryDBInfo() {
			return [];
			$info = $this->DBInfo();
			return [
				'username'=>$info['username'],
				'password'=>$info['password'],
				'hostname'=>'your host here',	# this should be the name of the server hosting this mysql db
				'database'=>'alldictionaries',
			];
		}
		
		public function NewRandomPasswordSeed() {
			return 'aoeuia4y';
		}
		
		public function SetSQLModePerSession() {
			return TRUE;
		}
		
		public function UseHeaderRedirects() {
			return TRUE;
		}
		
		public function SetAPIData() {
			return [];
		}
		
		public function RSSFeeds() {
			return [
			];
		}
		
		public function AdminEmailAddress() {
			return 'Your Admin Email Address';
		}
		
		public function CertificateAuthorityAdminEmailAddress() {
			return $this->AdminEmailAddress();
		}
		
		public function AdminName() {
			return 'Your Admin Username';
		}
		
		public function EncryptionAuthority() {
			return 'Your Encryption Authority in Domain Format';	# i.e., 'letsencrypt.org'
		}
		
		public function EnableDictionaries() {
			return TRUE;
		}
		
		public function EnableErrorLogging() {
			return TRUE;
		}
		
		public function EnableStats() {
			return TRUE;
		}
		
		public function EnableStats_LogMemoryUse() {
			return TRUE;
		}
		
		public function EnableStats_LogExcessiveMemoryUse() {
			return TRUE;
		}
		
		public function EnableStats_Log404Pages() {
			return FALSE;
		}
		
		public function EnableStats_LogExcessiveMemoryUse_MaxSize() {
			return 1000000;
		}
		
		public function minNewsItemsAllowed() {
			return 1;
		}
		
		public function maxNewsItemsAllowed() {
			return 1000;
		}
		
		public function defaultNewsItems() {
			return 20;
		}
		
		public function defaultNewsItems_RSS() {
			return 100;
		}
		
		public function buildRecordTreeForEntryid_MaxDepth() {
			return 10;
		}
		
		public function ModifiableFieldDisplay() {
			return [
				'Title'=>'Title',
				'Subtitle'=>'Subtitle',
				'List Title'=>'List Title',
				'List Title Sort Key'=>'List Title Sort Key',
				'Code'=>'Code',
				'Entry Translation'=>'Entry Translation',
				'Availability Start'=>'Availability Start',
				'Availability End'=>'Availability End',
				'Description'=>'Description',
				'Quote'=>'Quote',
				'Text Body'=>'Text Body',
				'Image'=>'Image',
				'Image Translation'=>'Image Translation',
				'Tag'=>'Tag',
				'Link'=>'Link',
				'Event Date'=>'Event Date',
				'Association'=>'People',			# this one is diff
				'Definition'=>'Definition',
			];
		}
		
		public function AssociationEntryCodes() {
			return [
				'*'=>[
					'people',		# Everyone else: Show people
				],
				'people'=>[			# Do not show associations for any entry where 'people' is the first or second entry
				],
			];
		}
		
		public function IndexMaxRandomChildren() {
			return 5;
		}
		
		public function IndexPullChildRecordStats() {
			return FALSE;
		}
		
			/* MaxParentGrammarControlDepth()
			
				Controls these fields:

					'Child Adjective',
					'Child Noun',
					'Child Noun Plural',
					'Grand Child Adjective',
					'Grand Child Noun',
					'Grand Child Noun Plural',
			
				If depth is 2, then these fields show at a/b/modify.php, but not at a/b/c/modify.php.
			
			*/
		
		public function MaxParentGrammarControlDepth() {
			return 1;
		}
		
		public function MaxParentAssociationControlDepth() {
			return 2;
		}
		
		public function TitleAutoIncrement() {
			return FALSE;
		}
		
		public function TitleAutoSmartTitleCase() {
			return TRUE;
		}
		
		public function SubTitleAutoSmartTitleCase() {
			return TRUE;
		}
		
		public function ListTitleAutoSmartTitleCase() {
			return TRUE;
		}
		
		public function TitleDeRomanizeNumbers() {
			return TRUE;
		}
		
		public function TitleAmericanize() {
			return TRUE;
		}
		
		public function AmericanizeTitleDefault() {
			return TRUE;
		}
		
		public function SubtitleAmericanize() {
			return TRUE;
		}
		
		public function AmericanizeSubtitleDefault() {
			return TRUE;
		}
		
		public function ListTitleAmericanize() {
			return TRUE;
		}
		
		public function AmericanizeListTitleDefault() {
			return TRUE;
		}
		
		public function RequiredFieldDepths() {
			return [
			];
		}
		
		public function ShowModifiableFields() {
			return [
				'Title'=>TRUE,
				'TitleAutoIncrement'=>$this->TitleAutoIncrement(),
				'Subtitle'=>TRUE,
				'ListTitle'=>TRUE,
				'ListTitleSortKey'=>TRUE,
				'Code'=>TRUE,
				'EntryTranslation'=>FALSE,
				'AvailabilityStart'=>FALSE,
				'AvailabilityEnd'=>FALSE,
				'Description'=>TRUE,
				'Quote'=>TRUE,
				'TextBody'=>TRUE,
				'Image'=>TRUE,
				'ImageTranslation'=>FALSE,
				'Tag'=>TRUE,
				'Link'=>TRUE,
				'EventDate'=>TRUE,
				'Association'=>TRUE,
				'Definition'=>FALSE,
				'Publish'=>TRUE,
				'Save'=>TRUE,
			];
		}
		
		public function AssociationEntryCodes_MaxDepth() {
			return 2;
		}
		
		public function DictionaryEnabled() {
			return TRUE;
		}
		
						// Config
						// -------------------------------------------------------------------
						
							// Add
							// -------------------------------------------------------------------
		
		public function AutoGenerateTitleDefault() {
			return FALSE;
		}
						
							// Add
							// -------------------------------------------------------------------
		
		public function AddEntryHTMLFormatting() {
			return FALSE;
		}
		
						// About Info
						// -------------------------------------------------------------------
		
		public function SiteCreator() {
			return 'SomeSiteCreator?';
		}
		
		public function SiteCreatedOn() {
			return 'SomeCreateDate?';
		}
		
		public function ContactCreator() {
			return 'SomeEmailAddress?';
		}
		
						// Main Menu Info
						// -------------------------------------------------------------------
						
							// Master List
							// -------------------------------------------------------------------
		
		public function MainMenu() {
			return [
				'home'=>[
					'url'=>$this->MainMenu_URL_Home(),
					'text'=>[
						'en'=>'Home',
						'de'=>'Zuhause',
						'es'=>'Casa',
						'fr'=>'Accueil',
						'ja'=>'ホーム',
						'it'=>'Casa',
						'nl'=>'Huis',
						'pl'=>'Dom',
						'pt'=>'Casa',
						'ru'=>'Главная',
						'tr'=>'Ev',
						'zh'=>'家',
					],
					'enabled'=>$this->MainMenu_Enabled_Home(),
				],
				'about'=>[
					'url'=>$this->MainMenu_URL_About(),
					'text'=>[
						'en'=>'About',
						'de'=>'Etwa',
						'es'=>'Acerca de',
						'fr'=>'Sur',
						'ja'=>'約',
						'it'=>'Di',
						'nl'=>'Over',
						'pl'=>'O',
						'pt'=>'Sobre',
						'ru'=>'Около',
						'tr'=>'hakkında',
						'zh'=>'关于',
					],
					'enabled'=>$this->MainMenu_Enabled_About(),
				],
				'contact'=>[
					'url'=>$this->MainMenu_URL_Contact(),
					'text'=>[
						'en'=>'Contact',
						'de'=>'Kontakt',
						'es'=>'Contacto',
						'fr'=>'Contact',
						'ja'=>'接触',
						'it'=>'Contatto',
						'nl'=>'Contact',
						'pl'=>'Kontakt',
						'pt'=>'Contato',
						'ru'=>'контакт',
						'tr'=>'Temas',
						'zh'=>'联系',
					],
					'enabled'=>$this->MainMenu_Enabled_Contact(),
				],
				'search'=>[
					'url'=>$this->MainMenu_URL_Search(),
					'text'=>[
						'en'=>'Search',
						'de'=>'Suche',
						'es'=>'Buscar',
						'fr'=>'Chercher',
						'ja'=>'サーチ',
						'it'=>'Ricerca',
						'nl'=>'Zoeken',
						'pl'=>'Szukaj',
						'pt'=>'Procurar',
						'ru'=>'Поиск',
						'tr'=>'Arama',
						'zh'=>'搜索',
					],
					'enabled'=>$this->MainMenu_Enabled_Search(),
				],
				'languages'=>[
					'url'=>$this->MainMenu_URL_Languages(),
					'text'=>[
						'en'=>'Languages',
						'de'=>'Sprachen',
						'es'=>'Idiomas',
						'fr'=>'Langues',
						'ja'=>'言語',
						'it'=>'Le lingue',
						'nl'=>'Talen',
						'pl'=>'Języki',
						'pt'=>'Idiomas',
						'ru'=>'Языки',
						'tr'=>'Diller',
						'zh'=>'语言',
					],
					'enabled'=>$this->MainMenu_Enabled_Languages(),
				],
				'privacypolicy'=>[
					'url'=>$this->MainMenu_URL_PrivacyPolicy(),
					'text'=>[
						'en'=>'Privacy Policy',
						'de'=>'Datenschutz-Bestimmungen',
						'es'=>'Política de privacidad',
						'fr'=>'Politique de confidentialité',
						'ja'=>'個人情報保護方針',
						'it'=>'politica sulla riservatezza',
						'nl'=>'Privacybeleid',
						'pl'=>'Polityka prywatności',
						'pt'=>'Política de Privacidade',
						'ru'=>'политика конфиденциальности',
						'tr'=>'Gizlilik Politikası',
						'zh'=>'隐私政策',
					],
					'enabled'=>$this->MainMenu_Enabled_PrivacyPolicy(),
				],
				'terms'=>[
					'url'=>$this->MainMenu_URL_Terms(),
					'text'=>[
						'en'=>'Terms and Conditions',
						'de'=>'Geschäftsbedingungen',
						'es'=>'Términos y Condiciones',
						'fr'=>'Termes et conditions',
						'ja'=>'規約と条件',
						'it'=>'Termini e condizioni',
						'nl'=>'Voorwaarden',
						'pl'=>'Regulamin',
						'pt'=>'Termos e Condições',
						'ru'=>'Условия и положения',
						'tr'=>'Şartlar ve koşullar',
						'zh'=>'条款和条件',
					],
					'enabled'=>$this->MainMenu_Enabled_Terms(),
				],
				'codeofconduct'=>[
					'url'=>$this->MainMenu_URL_CodeOfConduct(),
					'text'=>[
						'en'=>'Code of Conduct',
						'de'=>'Verhaltensregeln',
						'es'=>'Código de Conducta',
						'fr'=>'Code de conduite',
						'ja'=>'行動規範',
						'it'=>'Codice di condotta',
						'nl'=>'Gedragscode',
						'pl'=>'Kodeks postępowania',
						'pt'=>'Código de conduta',
						'ru'=>'Нормы поведения',
						'tr'=>'Davranış kodu',
						'zh'=>'行为守则',
					],
					'enabled'=>$this->MainMenu_Enabled_CodeOfConduct(),
				],
				'login'=>[
					'url'=>$this->MainMenu_URL_Login(),
					'text'=>[
						'en'=>'Login',
						'de'=>'Anmeldung',
						'es'=>'Iniciar sesión',
						'fr'=>'S\'identifier',
						'ja'=>'ログイン',
						'it'=>'Accesso',
						'nl'=>'Log in',
						'pl'=>'Zaloguj Się',
						'pt'=>'Entrar',
						'ru'=>'Авторизоваться',
						'tr'=>'Oturum aç',
						'zh'=>'登录',
					],
					'enabled'=>$this->MainMenu_Enabled_Login(),
				],
			];
		}
						
							// URLs
							// -------------------------------------------------------------------
		
		public function MainMenu_URL_Home() {
			return '/';
		}
		
		public function MainMenu_URL_About() {
			return '/about.php';
		}
		
		public function MainMenu_URL_Contact() {
			return '/contact.php';
		}
		
		public function MainMenu_URL_Search() {
			return '/search.php';
		}
		
		public function MainMenu_URL_Languages() {
			return '/languages.php';
		}
		
		public function MainMenu_URL_PrivacyPolicy() {
			return '/privacy.php';
		}
		
		public function MainMenu_URL_Terms() {
			return '/terms.php';
		}
		
		public function MainMenu_URL_CodeOfConduct() {
			return '/codeofconduct.php';
		}
		
		public function MainMenu_URL_Login() {
			return '/login.php';
		}
						
							// Enabled/Disabled
							// -------------------------------------------------------------------
		
		public function MainMenu_Enabled_Home() {
			return TRUE;
		}
		
		public function MainMenu_Enabled_About() {
			return TRUE;
		}
		
		public function MainMenu_Enabled_News() {
			return FALSE;
		}
		
		public function MainMenu_Enabled_Feeds() {
			return FALSE;
		}
		
		public function MainMenu_Enabled_Contact() {
			return TRUE;
		}
		
		public function MainMenu_Enabled_Search() {
			return FALSE;
		}
		
		public function MainMenu_Enabled_Languages() {
			return FALSE;
		}
		
		public function MainMenu_Enabled_PrivacyPolicy() {
			return TRUE;
		}
		
		public function MainMenu_Enabled_Terms() {
			return TRUE;
		}
		
		public function MainMenu_Enabled_CodeOfConduct() {
			return FALSE;
		}
		
		public function MainMenu_Enabled_Login() {
			return FALSE;
		}
		
		public function SiteCategory() {
			return '';
		}
		
		public function SiteLinks_ExtraURL() {
			return '';
		}
		
		public function useDBFileCache() {
			return TRUE;
		}
		
						// Styling Info
						// -------------------------------------------------------------------
		
		public function Styling() {
			return [
				'PrimaryColor'=>'6495ED',
				'SecondaryColor'=>'C2DFFF',
				'ThirdColor'=>'B7CEEC',
			];
		}
		
						// Functionality Info
						// -------------------------------------------------------------------
		
		public function SharingInfo() {
			return [
				'text'=>[
					'Share'=>[
						'en'=>'Share',
						'de'=>'Aktie',
						'es'=>'Compartir',
						'fr'=>'Partager',
						'ja'=>'シェア',
						'it'=>'Condividere',
						'nl'=>'Delen',
						'pl'=>'Dzielić',
						'pt'=>'Compartilhar',
						'ru'=>'Поделиться',
						'tr'=>'Pay',
						'zh'=>'分享',
					],
					'Share With'=>[
						'en'=>'Share With',
						'de'=>'Teilen mit',
						'es'=>'Compartir con',
						'fr'=>'Partager avec',
						'ja'=>'と共有する',
						'it'=>'Condividi con',
						'nl'=>'Delen met',
						'pl'=>'Udostępniać',
						'pt'=>'Compartilhar com',
						'ru'=>'Разрешить',
						'tr'=>'İle paylaş',
						'zh'=>'与某人分享',
					],
				],
			];
		}
		
		/*
			'en'=>'',
			'de'=>'',
			'es'=>'',
			'fr'=>'',
			'ja'=>'',
			'it'=>'',
			'nl'=>'',
			'pl'=>'',
			'pt'=>'',
			'ru'=>'',
			'tr'=>'',
			'zh'=>'',
		*/
	}
	
?>