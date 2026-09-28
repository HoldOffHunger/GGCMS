<?php

	/*
		Builds each site's one stylesheet from the engine's layers and the
		site's theme.  See Docs/Styling.md.

		The engine's sources are src/css/: the layer order, fonts, reset,
		tokens, base, the frozen legacy classes, then one file per component.
		A site's theme is templates/<site>/theme.css, which lives in the
		private configuration repository; it is wrapped in the "site" layer
		here, so it wins over everything else without !important.

		Every site gets a stylesheet.  One with no theme gets "default",
		which is the engine's layers alone.

		Output goes under the document root as css/build/<name>.<hash>.css,
		named by its content so that Cloudflare's four-hour max-age can
		never serve a stale one, plus stylesheets.json, which says which
		file each site uses.  ClientSideIncludes reads that to print the
		<link>.

		Old builds are left in place for a month.  A page in the page cache
		names the stylesheet it was rendered with, and deleting that file
		would unstyle the page until the cache is replaced.
	*/

	class StylesheetBuilder {
		public $engine_css_dir;
		public $template_dirs;
		public $output_dir;
		public $quiet;

			// Construction
			// -------------------------------------------------

		public function __construct($args) {
			$this->engine_css_dir = $args['enginecssdir'];
			$this->template_dirs = $args['templatedirs'];
			$this->output_dir = $args['outputdir'];
			$this->quiet = !empty($args['quiet']);
		}

			// The engine's layers, in cascade order.  Components are added
			// after these, sorted, so their order never depends on a
			// directory listing.

		public function EngineSources() {
			return [
				'layers.css',
				'fonts.css',
				'reset.css',
				'tokens.css',
				'base.css',
				'legacy.css',
			];
		}

		public function KeepOldBuildsForSeconds() {
			return 30 * 24 * 60 * 60;
		}

			// Building
			// -------------------------------------------------

		public function Build() {
			$engine = $this->EngineStylesheet();

			if($engine === FALSE) {
				return FALSE;
			}

			if(!is_dir($this->output_dir) && !mkdir($this->output_dir, 0755, TRUE)) {
				$this->Say(['text'=>'cannot create ' . $this->output_dir]);
				return FALSE;
			}

			$manifest = [];
			$manifest['default'] = $this->WriteStylesheet(['name'=>'default', 'css'=>$engine]);

			foreach($this->Themes() as $site=>$theme_file) {
				$theme = file_get_contents($theme_file);

				$css = $engine . "\n\n" . '@layer site {' . "\n\n" . $theme . "\n" . '}' . "\n";

				$manifest[$site] = $this->WriteStylesheet(['name'=>$site, 'css'=>$css]);
			}

			ksort($manifest);

			$manifest_file = $this->output_dir . 'stylesheets.json';
			$manifest_json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

				# write then rename, so a page never reads half a manifest
			file_put_contents($manifest_file . '.partial', $manifest_json);
			rename($manifest_file . '.partial', $manifest_file);

			$this->RemoveOldBuilds(['keep'=>array_values($manifest)]);

			foreach($manifest as $site=>$file) {
				$this->Say(['text'=>str_pad($site, 24) . ' ' . $file]);
			}

			return $manifest;
		}

		public function EngineStylesheet() {
			$css = '';

			foreach($this->EngineSources() as $source) {
				$location = $this->engine_css_dir . $source;

				if(!is_file($location)) {
					$this->Say(['text'=>'missing ' . $location]);
					return FALSE;
				}

				$css .= file_get_contents($location) . "\n\n";
			}

			$components = glob($this->engine_css_dir . 'components/*.css');
			sort($components);

			if($components) {
				$css .= '@layer components {' . "\n\n";

				foreach($components as $component) {
					$css .= file_get_contents($component) . "\n\n";
				}

				$css .= '}' . "\n";
			}

			return $css;
		}

			// Every template directory holding a theme.css, as site => file.
			// A site in both directories takes the later one: on a
			// workstation that is the configuration repository's copy.

		public function Themes() {
			$themes = [];

			foreach($this->template_dirs as $template_dir) {
				foreach((array) glob($template_dir . '*/theme.css') as $theme_file) {
					$site = basename(dirname($theme_file));

					if($site !== 'default') {
						$themes[$site] = $theme_file;
					}
				}
			}

			ksort($themes);

			return $themes;
		}

		public function WriteStylesheet($args) {
			$name = $args['name'];
			$css = $this->Compact(['css'=>$args['css']]);

			$file = $name . '.' . substr(sha1($css), 0, 10) . '.css';

			if(!is_file($this->output_dir . $file)) {
				file_put_contents($this->output_dir . $file . '.partial', $css);
				rename($this->output_dir . $file . '.partial', $this->output_dir . $file);
			}

			touch($this->output_dir . $file);

			return $file;
		}

			// Comments and blank lines only.  The stylesheet is small and
			// gzipped on the way out; anything cleverer risks changing what
			// a rule means.

		public function Compact($args) {
			$css = preg_replace('#/\*.*?\*/#s', '', $args['css']);
			$css = preg_replace("/[ \t]+\n/", "\n", $css);
			$css = preg_replace("/\n{3,}/", "\n\n", $css);

			return trim($css) . "\n";
		}

		public function RemoveOldBuilds($args) {
			$keep = array_flip($args['keep']);
			$cutoff = time() - $this->KeepOldBuildsForSeconds();

			foreach((array) glob($this->output_dir . '*.css') as $built) {
				if(!isset($keep[basename($built)]) && filemtime($built) < $cutoff) {
					unlink($built);
				}
			}

			return TRUE;
		}

		public function Say($args) {
			if(!$this->quiet) {
				print($args['text'] . "\n");
			}

			return TRUE;
		}
	}

?>
