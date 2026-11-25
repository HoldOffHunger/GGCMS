<?php

	// Source inventory only. Do not load web scripts or bootstrap a request.
	if(PHP_SAPI !== 'cli') {
		exit(1);
	}

	function scriptMapSource($args) {
		$source = file_get_contents($args['path']);
		$result = '';
		foreach(token_get_all($source) as $token) {
			if(is_string($token)) {
				$result .= $token;
			} elseif(in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])) {
				$result .= ' ';
			} else {
				$result .= $token[1];
			}
		}
		return $result;
	}

	function scriptMapGuard($args) {
		foreach($args['sources'] as $source) {
			$pattern = '/\bfunction\s+' . $args['method'] . '\s*\(\s*\)\s*\{/i';
			if(preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE)) {
				$body = substr($source, $match[0][1] + strlen($match[0][0]));
				if(preg_match('/^\s*return\s+(true|false)\s*;\s*\}/i', $body, $value)) {
					return strtolower($value[1]) === 'true';
				}
				return NULL;
			}
		}
		return NULL;
	}

	function scriptMapAccess($args) {
		$sources = $args['sources'];
		$accessible = scriptMapGuard(['sources'=>$sources, 'method'=>'IsAccessible']);
		$login = scriptMapGuard(['sources'=>$sources, 'method'=>'RequiresLogin']);
		$admin = scriptMapGuard(['sources'=>$sources, 'method'=>'AdminOnly']);
		return match(TRUE) {
			$accessible === FALSE => 'Disabled',
			$accessible === NULL || $login === NULL || ($login && $admin === NULL) => 'Conditional',
			$login && $admin => 'Admin',
			$login => 'Logged in',
			default => 'Anyone',
		};
	}

	function scriptMapActions($args) {
		$names = ['display', ...$args['extra']];
		foreach(glob($args['templates'] . '/*.php') as $template) {
			$names[] = pathinfo($template, PATHINFO_FILENAME);
		}
		$methods = [];
		foreach($args['sources'] as $source) {
			preg_match_all('/\bpublic\s+function\s+(\w+)\s*\(\s*\)/i', $source, $matches);
			foreach($matches[1] as $method) {
				$methods[strtolower($method)] ??= $method;
			}
		}
		$actions = array_intersect_key($methods, array_flip(array_map('strtolower', $names)));
		ksort($actions, SORT_NATURAL);
		return implode(', ', $actions) ?: '—';
	}

	function scriptMapRow($args) {
		$cells = array_map(static fn($cell) => str_replace('|', '\\|', $cell), $args['cells']);
		return '| ' . implode(' | ', $cells) . " |\n";
	}

	$usage = "Usage: php script_map.php [--output=PATH] [--overlay=CONFIG_REPO]\n";
	if($argv === [$argv[0], '--help']) {
		print($usage);
		exit(0);
	}
	$options = [];
	foreach(array_slice($argv, 1) as $argument) {
		if(!preg_match('/^--(output|overlay)=(.+)$/D', $argument, $match) || isset($options[$match[1]])) {
			fwrite(STDERR, $usage);
			exit(1);
		}
		$options[$match[1]] = $match[2];
	}

	$source_root = dirname(__DIR__) . '/src';
	$script_root = $source_root . '/scripts';
	$catalog = json_decode(file_get_contents(__DIR__ . '/data/script_purposes.json'), TRUE, flags: JSON_THROW_ON_ERROR);
	require($source_root . '/classes/Cache/PageCache.php');
	$page_cache = new PageCache(['handler'=>NULL]);
	$html_cache = $page_cache->CacheableScripts();
	$format_cache = $page_cache->CacheableFormats();

	$sources = [];
	foreach(glob($script_root . '/*.php') as $path) {
		$sources[basename($path)] = scriptMapSource(['path'=>$path]);
	}
	$base = scriptMapSource(['path'=>$script_root . '/Format/base_format.php']);
	$format_bases = [];
	foreach(glob($script_root . '/Format/*/basicscript.php') as $path) {
		$format_bases[basename(dirname($path))] = $path;
	}
	$html_base = scriptMapSource(['path'=>$format_bases['HTML']]);
	$groups = $catalog['groups'];
	$uncataloged = array_diff_key($sources, ...array_values($groups));
	if($uncataloged) {
		$groups['Uncatalogued'] = array_fill_keys(array_keys($uncataloged), 'Purpose pending.');
	}

	$output = "# GGCMS script map\n\n";
	$output .= "Regenerate the shared map from the repository root with:\n\n";
	$output .= "```sh\nphp usr/lib/ggcms/cli/script_map.php --output=Docs/Scripts.md\n```\n\n";
	$output .= "Access describes the script-level guards for HTML. Entry visibility, action checks, and site settings can further restrict an individual request.\n";
	$output .= "Actions are public zero-argument methods with a matching default template, the default display method, or a known non-template action recorded in the catalogue.\n";
	$output .= "HTML cache indicates membership in the engine allowlist. Request and response checks still apply. See `Docs/PageCache.md` for details.\n";

	$script_details = [];
	foreach($groups as $group => $items) {
		$output .= "\n## " . $group . "\n\n";
		$output .= "| Script | Purpose | Access | HTML cache | Actions |\n| --- | --- | --- | --- | --- |\n";
		foreach(array_intersect_key($items, $sources) as $name => $purpose) {
			$source = $sources[$name];
			$chain = [$source];
			if(preg_match('/\bclass\s+\w+\s+extends\s+view\s*\{/i', $source)) {
				$chain[] = $sources['view.php'];
			}
			$chain[] = $html_base;
			$chain[] = $base;
			$actions = scriptMapActions([
				'sources'=>$chain,
				'templates'=>$source_root . '/templates/default/' . pathinfo($name, PATHINFO_FILENAME),
				'extra'=>$catalog['actions'][$name] ?? [],
			]);
			$script_details[$name] = [
				scriptMapAccess(['sources'=>$chain]),
				in_array($name, $html_cache, TRUE) ? 'Eligible' : '—', $actions,
			];
			$output .= scriptMapRow(['cells'=>['`' . $name . '`', $purpose, ...$script_details[$name]]]);
		}
	}

	$output .= "\n## Format support\n\n";
	$output .= "These bases support the routed scripts. Access and actions come from the selected script.\n\n";
	$output .= "| File under `scripts/Format/` | Purpose | Format cache |\n| --- | --- | --- |\n";
	$output .= scriptMapRow(['cells'=>['`base_format.php`', $catalog['formats']['base_format.php'], '—']]);
	foreach($format_bases as $name => $path) {
		$output .= scriptMapRow(['cells'=>[
			'`' . $name . '/' . basename($path) . '`',
			$catalog['formats'][$name] ?? 'Purpose pending.',
			array_key_exists($name, $format_cache) ? 'Eligible' : '—',
		]]);
	}

	$output .= "\n## Private overlay\n\n";
	$output .= "For a local inventory that includes configuration overrides, pass `--overlay=CONFIG_REPO` and write the result outside the public checkout.\n";
	if(isset($options['overlay'])) {
		$overlay_root = rtrim($options['overlay'], '/\\') . '/etc/ggcms';
		if(!is_dir($overlay_root)) {
			fwrite(STDERR, "Overlay must point to a configuration repository containing etc/ggcms.\n");
			exit(1);
		}
		$output .= "\nThese files configure the corresponding engine scripts. The access, HTML cache, and actions columns describe the shared script involved.\n\n";
		$output .= "| Configuration file under `etc/ggcms/` | Purpose | Access | HTML cache | Actions |\n| --- | --- | --- | --- | --- |\n";
		foreach(glob($overlay_root . '/*/scripts/*.php') as $path) {
			$name = basename($path);
			$relative_path = basename(dirname($path, 2)) . '/scripts/' . $name;
			$output .= scriptMapRow(['cells'=>[
				'`' . $relative_path . '`', $catalog['overlay'][$name] ?? 'Purpose pending.',
				...($script_details[$name] ?? ['—', '—', '—']),
			]]);
		}
	}

	if(isset($options['output'])) {
		exit(file_put_contents($options['output'], $output) === FALSE ? 1 : 0);
	}
	print($output);
