<?php

		/*
			The Systems panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #2, System Testing : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'PHP Tests',
			'link'=>' ',
			'mouseover'=>'PHP Tests and Experiments',
			[
				'text'=>'Run PHPInfo()',
				'link'=>'systemstatus.php?action=PHPInfo',
				'mouseover'=>'Run and display the results of the \'phpinfo()\' function.',
			],
			[
				'text'=>'Run PHPCredits()',
				'link'=>'systemstatus.php?action=PHPCredits',
				'mouseover'=>'Run and display the results of the \'phpcredits()\' function.',
			],
			[
				'text'=>'Run PHPVersion()',
				'link'=>'systemstatus.php?action=PHPVersion',
				'mouseover'=>'Run and display the results of the \'phpversion()\' function.',
			],
			[
				'text'=>'Run PHP_UName()',
				'link'=>'systemstatus.php?action=PHPUname',
				'mouseover'=>'Run and display the results of the \'php_uname()\' function.',
			],
			[
				'text'=>'Run PHP_SAPI_Name()',
				'link'=>'systemstatus.php?action=PHPSapiName',
				'mouseover'=>'Run and display the results of the \'php_sapi_name()\' function.',
			],
			[
				'text'=>'Run PHP_INI_Scanned_Files()',
				'link'=>'systemstatus.php?action=PHPINIScannedFiles',
				'mouseover'=>'Run and display the results of the \'php_ini_scanned_files()\' function.',
			],
			[
				'text'=>'Run PHP_INI_Loaded_File()',
				'link'=>'systemstatus.php?action=PHPINILoadedFile',
				'mouseover'=>'Run and display the results of the \'php_ini_loaded_file()\' function.',
			],
			[
				'text'=>'Run Memory_Get_Usage()',
				'link'=>'systemstatus.php?action=MemoryGetUsage',
				'mouseover'=>'Run and display the results of the \'memory_get_usage()\' function.',
			],
			[
				'text'=>'Run Memory_Get_Peak_Usage()',
				'link'=>'systemstatus.php?action=MemoryGetPeakUsage',
				'mouseover'=>'Run and display the results of the \'memory_get_peak_usage()\' function.',
			],
			[
				'text'=>'Run INI_Get_All()',
				'link'=>'systemstatus.php?action=INIGetAll',
				'mouseover'=>'Run and display the results of the \'ini_get_all()\' function.',
			],
			[
				'text'=>'Run GetRUsage()',
				'link'=>'systemstatus.php?action=GetRUsage',
				'mouseover'=>'Run and display the results of the \'getrusage()\' function.',
			],
			[
				'text'=>'Run GetOpt()',
				'link'=>'systemstatus.php?action=GetOpt',
				'mouseover'=>'Run and display the results of the \'getopt()\' function.',
			],
			[
				'text'=>'Run Get_Include_Path()',
				'link'=>'systemstatus.php?action=GetIncludePath',
				'mouseover'=>'Run and display the results of the \'get_include_path()\' function.',
			],
			[
				'text'=>'Run Get_CFG_Var()',
				'link'=>'systemstatus.php?action=GetCFGVar',
				'mouseover'=>'Run and display the results of the \'get_cfg_var()\' function.',
			],
			[
				'text'=>'Run GC_Enabled()',
				'link'=>'systemstatus.php?action=GCEnabled',
				'mouseover'=>'Run and display the results of the \'gc_enabled()\' function.',
			],
		],
		[
			'text'=>'Server Tests',
			'link'=>' ',
			'mouseover'=>'Server Tests and Experiments',
			[
				'text'=>'Run Sys_Get_Temp_Dir()',
				'link'=>'systemstatus.php?action=SysGetTempDir',
				'mouseover'=>'Run and display the results of the \'sys_get_temp_dir()\' function.',
			],
			[
				'text'=>'Run GetMyUID()',
				'link'=>'systemstatus.php?action=GetMyUID',
				'mouseover'=>'Run and display the results of the \'get_my_uid()\' function.',
			],
			[
				'text'=>'Run GetMyInode()',
				'link'=>'systemstatus.php?action=GetMyInode',
				'mouseover'=>'Run and display the results of the \'getmyinode()\' function.',
			],
			[
				'text'=>'Run GetMyGid()',
				'link'=>'systemstatus.php?action=GetMyGid',
				'mouseover'=>'Run and display the results of the \'getmygid()\' function.',
			],
			[
				'text'=>'Run GetLastMod()',
				'link'=>'systemstatus.php?action=GetLastMod',
				'mouseover'=>'Run and display the results of the \'getlastmod()\' function.',
			],
			[
				'text'=>'Run Get_Current_User()',
				'link'=>'systemstatus.php?action=GetCurrentUser',
				'mouseover'=>'Run and display the results of the \'get_current_user()\' function.',
			],
			[
				'text'=>'Run CLI_Get_Process_Title()',
				'link'=>'systemstatus.php?action=CLIGetProcessTitle',
				'mouseover'=>'Run and display the results of the \'cli_get_process_title()\' function.',
			],
			[
				'text'=>'Run GetAllHeaders()',
				'link'=>'systemstatus.php?action=GetAllHeaders',
				'mouseover'=>'Run and display the results of the \'get_all_headers()\' function.',
			],
			[
				'text'=>'Run Apache_Response_Headers()',
				'link'=>'systemstatus.php?action=ApacheResponseHeaders',
				'mouseover'=>'Run and display the results of the \'apache_response_headers()\' function.',
			],
			[
				'text'=>'Run Apache_Request_Headers()',
				'link'=>'systemstatus.php?action=ApacheRequestHeaders',
				'mouseover'=>'Run and display the results of the \'apache_request_headers()\' function.',
			],
			[
				'text'=>'Run Apache_Lookup_URI()',
				'link'=>'systemstatus.php?action=ApacheLookupURI',
				'mouseover'=>'Run and display the results of the \'apache_lookup_uri()\' function.',
			],
			[
				'text'=>'Run Apache_GetEnv()',
				'link'=>'systemstatus.php?action=ApacheGetEnv',
				'mouseover'=>'Run and display the results of the \'apache_getenv()\' function.',
			],
			[
				'text'=>'Run Apache_Get_Version()',
				'link'=>'systemstatus.php?action=ApacheGetVersion',
				'mouseover'=>'Run and display the results of the \'apache_get_version()\' function.',
			],
			[
				'text'=>'Run Apache_Get_Modules()',
				'link'=>'systemstatus.php?action=ApacheGetModules',
				'mouseover'=>'Run and display the results of the \'apache_get_modules()\' function.',
			],
		],
		[
			'text'=>'Networking Tests',
			'link'=>' ',
			'mouseover'=>'Networking Tests and Experiments',
			[
				'text'=>'Run INet_PTon()',
				'link'=>'systemstatus.php?action=INetPTon',
				'mouseover'=>'Run and display the results of the \'inet_pton()\' function.',
			],
			[
				'text'=>'Run INet_NTop()',
				'link'=>'systemstatus.php?action=INetNTop',
				'mouseover'=>'Run and display the results of the \'inet_ntop()\' function.',
			],
			[
				'text'=>'Run IP2Long()',
				'link'=>'systemstatus.php?action=IP2Long',
				'mouseover'=>'Run and display the results of the \'ip2long()\' function.',
			],
			[
				'text'=>'Run Long2IP()',
				'link'=>'systemstatus.php?action=Long2IP',
				'mouseover'=>'Run and display the results of the \'long2ip()\' function.',
			],
			[
				'text'=>'Run HTTP_Response_Code()',
				'link'=>'systemstatus.php?action=HTTPResponseCode',
				'mouseover'=>'Run and display the results of the \'http_response_code()\' function.',
			],
			[
				'text'=>'Run Headers_Sent()',
				'link'=>'systemstatus.php?action=HeadersSent',
				'mouseover'=>'Run and display the results of the \'headers_sent()\' function.',
			],
			[
				'text'=>'Run Headers_List()',
				'link'=>'systemstatus.php?action=HeadersList',
				'mouseover'=>'Run and display the results of the \'headers_list()\' function.',
			],
			[
				'text'=>'Run GetServByPort()',
				'link'=>'systemstatus.php?action=GetServByPort',
				'mouseover'=>'Run and display the results of the \'getservbyport()\' function.',
			],
			[
				'text'=>'Run GetServByName()',
				'link'=>'systemstatus.php?action=GetServByName',
				'mouseover'=>'Run and display the results of the \'getservbyname()\' function.',
			],
			[
				'text'=>'Run GetProtoByNumber()',
				'link'=>'systemstatus.php?action=GetProtoByNumber',
				'mouseover'=>'Run and display the results of the \'getprotobynumber()\' function.',
			],
			[
				'text'=>'Run GetProtoByName()',
				'link'=>'systemstatus.php?action=GetProtoByName',
				'mouseover'=>'Run and display the results of the \'getprotobyname()\' function.',
			],
			[
				'text'=>'Run GetMXRR()',
				'link'=>'systemstatus.php?action=GetMXRR',
				'mouseover'=>'Run and display the results of the \'getmxrr()\' function.',
			],
			[
				'text'=>'Run GetHostName()',
				'link'=>'systemstatus.php?action=GetHostName',
				'mouseover'=>'Run and display the results of the \'gethostname()\' function.',
			],
			[
				'text'=>'Run GetHostByNameL()',
				'link'=>'systemstatus.php?action=GetHostByNameL',
				'mouseover'=>'Run and display the results of the \'gethostbynamel()\' function.',
			],
			[
				'text'=>'Run GetHostByName()',
				'link'=>'systemstatus.php?action=GetHostByName',
				'mouseover'=>'Run and display the results of the \'gethostbyname()\' function.',
			],
			[
				'text'=>'Run GetHostByAddr()',
				'link'=>'systemstatus.php?action=GetHostByAddr',
				'mouseover'=>'Run and display the results of the \'gethostbyaddr()\' function.',
			],
			[
				'text'=>'Run DNS_Get_Record()',
				'link'=>'systemstatus.php?action=DNSGetRecord',
				'mouseover'=>'Run and display the results of the \'dns_get_record()\' function.',
			],
			[
				'text'=>'Run CheckDNSRR()',
				'link'=>'systemstatus.php?action=CheckDNSRR',
				'mouseover'=>'Run and display the results of the \'checkdnsrr()\' function.',
			],
		],
		[
			'text'=>'Programming Tests',
			'link'=>' ',
			'mouseover'=>'Programming Tests and Experiments',
			[
				'text'=>'View $_COOKIE',
				'link'=>'systemstatus.php?action=ViewCookieVariable',
				'mouseover'=>'Run and display the results of the print_r($_COOKIE) function.',
			],
			[
				'text'=>'View $_SERVER',
				'link'=>'systemstatus.php?action=ViewServerVariable',
				'mouseover'=>'Run and display the results of the print_r($_SERVER) function.',
			],
			[
				'text'=>'View $_POST',
				'link'=>'systemstatus.php?action=ViewPostVariable',
				'mouseover'=>'Run and display the results of the print_r($_POST) function.',
			],
			[
				'text'=>'View $_GET',
				'link'=>'systemstatus.php?action=ViewGetVariable',
				'mouseover'=>'Run and display the results of the print_r($_GET) function.',
			],
			[
				'text'=>'Run Extension_Loaded()',
				'link'=>'systemstatus.php?action=ExtensionLoaded',
				'mouseover'=>'Run and display the results of the \'extension_loaded()\' function.',
			],
			[
				'text'=>'Run Assert_Options()',
				'link'=>'systemstatus.php?action=AssertOptions',
				'mouseover'=>'Run and display the results of the \'assert_options()\' function.',
			],
			[
				'text'=>'Run Get_Extension_Funcs()',
				'link'=>'systemstatus.php?action=GetExtensionFuncs',
				'mouseover'=>'Run and display the results of the \'get_extension_funcs()\' function.',
			],
			[
				'text'=>'Run Get_Defined_Constants()',
				'link'=>'systemstatus.php?action=GetDefinedConstants',
				'mouseover'=>'Run and display the results of the \'get_defined_constants()\' function.',
			],
			[
				'text'=>'Run Get_Required_Files()',
				'link'=>'systemstatus.php?action=GetRequiredFiles',
				'mouseover'=>'Run and display the results of the \'get_required_files()\' function.',
			],
			[
				'text'=>'Run Get_Loaded_Extensions()',
				'link'=>'systemstatus.php?action=GetLoadedExtensions',
				'mouseover'=>'Run and display the results of the \'get_loaded_extensions()\' function.',
			],
			[
				'text'=>'Run Get_Included_Files()',
				'link'=>'systemstatus.php?action=GetIncludedFiles',
				'mouseover'=>'Run and display the results of the \'get_included_files()\' function.',
			],
			[
				'text'=>'Run Trait_Exists()',
				'link'=>'systemstatus.php?action=TraitExists',
				'mouseover'=>'Run and display the results of the \'trait_exists()\' function.',
			],
			[
				'text'=>'Run Property_Exists()',
				'link'=>'systemstatus.php?action=PropertyExists',
				'mouseover'=>'Run and display the results of the \'property_exists()\' function.',
			],
			[
				'text'=>'Run Method_Exists()',
				'link'=>'systemstatus.php?action=MethodExists',
				'mouseover'=>'Run and display the results of the \'method_exists()\' function.',
			],
			[
				'text'=>'Run Is_Subclass_Of()',
				'link'=>'systemstatus.php?action=IsSubclassOf',
				'mouseover'=>'Run and display the results of the \'is_subclass_of()\' function.',
			],
			[
				'text'=>'Run Interface_Exists()',
				'link'=>'systemstatus.php?action=InterfaceExists',
				'mouseover'=>'Run and display the results of the \'interface_exists()\' function.',
			],
			[
				'text'=>'Run Get_Parent_Class()',
				'link'=>'systemstatus.php?action=GetParentClass',
				'mouseover'=>'Run and display the results of the \'get_parent_class()\' function.',
			],
			[
				'text'=>'Run Get_Declared_Traits()',
				'link'=>'systemstatus.php?action=GetDeclaredTraits',
				'mouseover'=>'Run and display the results of the \'get_declared_traits()\' function.',
			],
			[
				'text'=>'Run Get_Declared_Interfaces()',
				'link'=>'systemstatus.php?action=GetDeclaredInterfaces',
				'mouseover'=>'Run and display the results of the \'get_declared_interfaces()\' function.',
			],
			[
				'text'=>'Run Get_Declared_Classes()',
				'link'=>'systemstatus.php?action=GetDeclaredClasses',
				'mouseover'=>'Run and display the results of the \'get_declared_classes()\' function.',
			],
			[
				'text'=>'Run Get_Class_Vars()',
				'link'=>'systemstatus.php?action=GetClassVars',
				'mouseover'=>'Run and display the results of the \'get_class_vars()\' function.',
			],
			[
				'text'=>'Run Get_Class_Methods()',
				'link'=>'systemstatus.php?action=GetClassMethods',
				'mouseover'=>'Run and display the results of the \'get_class_methods()\' function.',
			],
			[
				'text'=>'Run Get_Called_Class()',
				'link'=>'systemstatus.php?action=GetCalledClass',
				'mouseover'=>'Run and display the results of the \'get_called_class()\' function.',
			],
			[
				'text'=>'Run Class_Exists()',
				'link'=>'systemstatus.php?action=ClassExists',
				'mouseover'=>'Run and display the results of the \'class_exists()\' function.',
			],
			[
				'text'=>'Run Get_Defined_Functions()',
				'link'=>'systemstatus.php?action=GetDefinedFunctions',
				'mouseover'=>'Run and display the results of the \'get_defined_functions()\' function.',
			],
			[
				'text'=>'Run Function_Exists()',
				'link'=>'systemstatus.php?action=FunctionExists',
				'mouseover'=>'Run and display the results of the \'function_exists()\' function.',
			],
			[
				'text'=>'Run Func_Num_Args()',
				'link'=>'systemstatus.php?action=FuncNumArgs',
				'mouseover'=>'Run and display the results of the \'func_num_args()\' function.',
			],
			[
				'text'=>'Run Func_Get_Args()',
				'link'=>'systemstatus.php?action=FuncGetArgs',
				'mouseover'=>'Run and display the results of the \'func_get_args()\' function.',
			],
			[
				'text'=>'Run Is_String()',
				'link'=>'systemstatus.php?action=IsString',
				'mouseover'=>'Run and display the results of the \'is_string()\' function.',
			],
			[
				'text'=>'Run Is_Scalar()',
				'link'=>'systemstatus.php?action=IsScalar',
				'mouseover'=>'Run and display the results of the \'is_scalar()\' function.',
			],
			[
				'text'=>'Run Is_Resource()',
				'link'=>'systemstatus.php?action=IsResource',
				'mouseover'=>'Run and display the results of the \'is_resource()\' function.',
			],
			[
				'text'=>'Run Is_Object()',
				'link'=>'systemstatus.php?action=IsObject',
				'mouseover'=>'Run and display the results of the \'is_object()\' function.',
			],
			[
				'text'=>'Run Is_Numeric()',
				'link'=>'systemstatus.php?action=IsNumeric',
				'mouseover'=>'Run and display the results of the \'is_numeric()\' function.',
			],
			[
				'text'=>'Run Is_Null()',
				'link'=>'systemstatus.php?action=IsNull',
				'mouseover'=>'Run and display the results of the \'is_null()\' function.',
			],
			[
				'text'=>'Run Is_Long()',
				'link'=>'systemstatus.php?action=IsLong',
				'mouseover'=>'Run and display the results of the \'is_long()\' function.',
			],
			[
				'text'=>'Run Is_Float()',
				'link'=>'systemstatus.php?action=IsFloat',
				'mouseover'=>'Run and display the results of the \'is_float()\' function.',
			],
			[
				'text'=>'Run Is_Double()',
				'link'=>'systemstatus.php?action=IsDouble',
				'mouseover'=>'Run and display the results of the \'is_double()\' function.',
			],
			[
				'text'=>'Run Is_Callable()',
				'link'=>'systemstatus.php?action=IsCallable',
				'mouseover'=>'Run and display the results of the \'is_callable()\' function.',
			],
			[
				'text'=>'Run Is_Bool()',
				'link'=>'systemstatus.php?action=IsBool',
				'mouseover'=>'Run and display the results of the \'is_bool()\' function.',
			],
			[
				'text'=>'Run Is_Array()',
				'link'=>'systemstatus.php?action=IsArray',
				'mouseover'=>'Run and display the results of the \'is_array()\' function.',
			],
			[
				'text'=>'Run IntVal()',
				'link'=>'systemstatus.php?action=IntVal',
				'mouseover'=>'Run and display the results of the \'intval()\' function.',
			],
			[
				'text'=>'Run GetType()',
				'link'=>'systemstatus.php?action=GetType',
				'mouseover'=>'Run and display the results of the \'gettype()\' function.',
			],
			[
				'text'=>'Run Get_Resource_Type()',
				'link'=>'systemstatus.php?action=GetResourceType',
				'mouseover'=>'Run and display the results of the \'get_resource_type()\' function.',
			],
			[
				'text'=>'Run Get_Defined_Vars()',
				'link'=>'systemstatus.php?action=GetDefinedVars',
				'mouseover'=>'Run and display the results of the \'get_defined_vars()\' function.',
			],
			[
				'text'=>'Run FloatVal()',
				'link'=>'systemstatus.php?action=FloatVal',
				'mouseover'=>'Run and display the results of the \'floatval()\' function.',
			],
			[
				'text'=>'Run Empty()',
				'link'=>'systemstatus.php?action=EmptyFunc',
				'mouseover'=>'Run and display the results of the \'empty()\' function.',
			],
			[
				'text'=>'Run BoolVal()',
				'link'=>'systemstatus.php?action=BoolVal',
				'mouseover'=>'Run and display the results of the \'boolval()\' function.',
			],
		],
		[
			'text'=>'Cryptography Tests',
			'link'=>' ',
			'mouseover'=>'Cryptography Tests and Experiments',
			[
				'text'=>'Hash Extension',
				'link'=>' ',
				'mouseover'=>'Hash-Extension Cryptography Tests and Experiments',
				[
					'text'=>'Hash_Algos()',
					'link'=>'systemstatus.php?action=HashAlgos',
					'mouseover'=>'Run and display the results of the \'hash_algos()\' function.',
				],
				[
					'text'=>'Hash()',
					'link'=>'systemstatus.php?action=Hash',
					'mouseover'=>'Run and display the results of the \'hash()\' function.',
				],
				[
					'text'=>'Hash_File()',
					'link'=>'systemstatus.php?action=HashFile',
					'mouseover'=>'Run and display the results of the \'hash_file()\' function.',
				],
			],
			[
				'text'=>'MHash Extension',
				'link'=>' ',
				'mouseover'=>'MHash-Extension Cryptography Tests and Experiments',
				[
					'text'=>'MHash_Count()',
					'link'=>'systemstatus.php?action=MHashCount',
					'mouseover'=>'Run and display the results of the \'mhash_count()\' function.',
				],
				[
					'text'=>'MHash_Get_Block_Size()',
					'link'=>'systemstatus.php?action=MHashGetBlockSize',
					'mouseover'=>'Run and display the results of the \'mhash_get_block_size()\' function.',
				],
				[
					'text'=>'MHash_Get_Hash_Name()',
					'link'=>'systemstatus.php?action=MHashGetHashName',
					'mouseover'=>'Run and display the results of the \'mhash_get_hash_name()\' function.',
				],
				[
					'text'=>'MHash()',
					'link'=>'systemstatus.php?action=MHash',
					'mouseover'=>'Run and display the results of the \'mhash()\' function.',
				],
			],
			[
				'text'=>'MCrypt Extension',
				'link'=>' ',
				'mouseover'=>'MCrypt-Extension Cryptography Tests and Experiments',
				[		// BT: Implement me!
					'text'=>'MCrypt_Create_IV()',
					'link'=>'systemstatus.php?action=MCryptCreateIV',
					'mouseover'=>'Run and display the results of the \'mcrypt_create_iv()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Decrypt()',
					'link'=>'systemstatus.php?action=MCryptDecrypt',
					'mouseover'=>'Run and display the results of the \'mcrypt_decrypt()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_ECB()',
					'link'=>'systemstatus.php?action=MCryptECB',
					'mouseover'=>'Run and display the results of the \'mcrypt_ecb()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_Algorithms_Name()',
					'link'=>'systemstatus.php?action=MCryptEncGetAlgorithmsName',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_algorithms_name()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_Block_Size()',
					'link'=>'systemstatus.php?action=MCryptEncGetBlockSize',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_block_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_IV_Size()',
					'link'=>'systemstatus.php?action=MCryptEncGetIVSize',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_iv_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_Key_Size()',
					'link'=>'systemstatus.php?action=MCryptEncGetKeySize',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_key_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_Modes_Name()',
					'link'=>'systemstatus.php?action=MCryptEncGetModesName',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_modes_name()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Get_Supported_Key_Sizes()',
					'link'=>'systemstatus.php?action=MCryptEncGetSupportedKeySizes',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_get_supported_key_sizes()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Is_Block_Algorithm_Mode()',
					'link'=>'systemstatus.php?action=MCryptEncIsBlockAlgorithmMode',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_is_block_algorithm_mode()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Is_Block_Algorithm()',
					'link'=>'systemstatus.php?action=MCryptEncIsBlockAlgorithm',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_is_block_algorithm()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Is_Block_Mode()',
					'link'=>'systemstatus.php?action=MCryptEncIsBlockMode',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_is_block_mode()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Enc_Self_Test()',
					'link'=>'systemstatus.php?action=MCryptEncSelfTest',
					'mouseover'=>'Run and display the results of the \'mcrypt_enc_self_test()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Encrypt()',
					'link'=>'systemstatus.php?action=MCryptEncrypt',
					'mouseover'=>'Run and display the results of the \'mcrypt_encrypt()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Generic()',
					'link'=>'systemstatus.php?action=MCryptGeneric',
					'mouseover'=>'Run and display the results of the \'mycrypt_generic()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Get_Block_Size()',
					'link'=>'systemstatus.php?action=MCryptGetBlockSize',
					'mouseover'=>'Run and display the results of the \'mycrypt_get_block_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Get_Cipher_Name()',
					'link'=>'systemstatus.php?action=MCryptGetCipherName',
					'mouseover'=>'Run and display the results of the \'mycrypt_get_cipher_name()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Get_IV_Size()',
					'link'=>'systemstatus.php?action=MCryptGetIVSize',
					'mouseover'=>'Run and display the results of the \'mycrypt_get_iv_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Get_Key_Size()',
					'link'=>'systemstatus.php?action=MCryptGetKeySize',
					'mouseover'=>'Run and display the results of the \'mycrypt_get_key_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_List_Algorithms()',
					'link'=>'systemstatus.php?action=MCryptListAlgorithms',
					'mouseover'=>'Run and display the results of the \'mycrypt_list_algorithms()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_List_Modes()',
					'link'=>'systemstatus.php?action=MCryptListModes',
					'mouseover'=>'Run and display the results of the \'mycrypt_list_modes()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Get_Algo_Block_Size()',
					'link'=>'systemstatus.php?action=MCryptModuleGetAlgoBlockSize',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_get_algo_block_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Get_Algo_Key_Size()',
					'link'=>'systemstatus.php?action=MCryptModuleGetAlgoKeySize',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_get_algo_key_size()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Get_Supported_Key_Sizes()',
					'link'=>'systemstatus.php?action=MCryptModuleGetSupportedKeySizes',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_get_supported_key_sizes()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Is_Block_Algorithm_Mode()',
					'link'=>'systemstatus.php?action=MCryptModuleIsBlockAlgorithmMode',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_is_block_algorithm_mode()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Is_Block_Algorithm()',
					'link'=>'systemstatus.php?action=MCryptModuleIsBlockAlgorithm',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_is_block_algorithm()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Is_Block_Mode()',
					'link'=>'systemstatus.php?action=MCryptModuleIsBlockMode',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_is_block_mode()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Open()',
					'link'=>'systemstatus.php?action=MCryptModuleOpen',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_open()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MCrypt_Module_Self_Test()',
					'link'=>'systemstatus.php?action=MCryptModuleSelfTest',
					'mouseover'=>'Run and display the results of the \'mycrypt_module_self_test()\' function.',
				],
				[		// BT: Implement me!
					'text'=>'MDecrypt_Generic()',
					'link'=>'systemstatus.php?action=MDecryptGeneric',
					'mouseover'=>'Run and display the results of the \'mdecrypt_generic()\' function.',
				],
			],
			[
				'text'=>'CSPRNG Extension',
				'link'=>' ',
				'mouseover'=>'CSPRNG-Extension Cryptography Tests and Experiments',
				[
					'text'=>'Random_Bytes()',
					'link'=>'systemstatus.php?action=RandomBytes',
					'mouseover'=>'Run and display the results of the \'random_bytes()\' function.',
				],
				[
					'text'=>'Random_Int()',
					'link'=>'systemstatus.php?action=RandomInt',
					'mouseover'=>'Run and display the results of the \'random_int()\' function.',
				],
			],
		],
		[
			'text'=>'BCE Tests',
			'link'=>' ',
			'mouseover'=>'BCE Tests and Experiments',
			[
				'text'=>'Show Master Variables',
				'link'=>'systemstatus.php?action=ShowMasterVariables',
				'mouseover'=>'Shows the master variables.',
			],
			[
				'text'=>'Base::ConvertBase()',
				'link'=>'systemstatus.php?action=BaseConvertBase',
				'mouseover'=>'Run and display the results of the \'ConvertBase()\' function.',
			],
			[
				'text'=>'Binary::IncrementBinaryValue()',
				'link'=>'systemstatus.php?action=BinaryIncrementBinaryValue',
				'mouseover'=>'Run and display the results of the \'IncrementBinaryValue()\' function.',
			],
			[
				'text'=>'Binary::DecrementBinaryValue()',
				'link'=>'systemstatus.php?action=BinaryDecrementBinaryValue',
				'mouseover'=>'Run and display the results of the \'DecrementBinaryValue()\' function.',
			],
		],
	];

?>
