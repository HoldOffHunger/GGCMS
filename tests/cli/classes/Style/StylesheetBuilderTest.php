<?php

	/*
		StylesheetBuilder joins the engine's layers and each site's theme
		into one stylesheet per site, named by its content.  Built here
		from fixture sources in the test's scratch directory.
	*/

	class StylesheetBuilderTest extends GGCMSTestCase {
		public function newBuilder($args) {
			$this->requireCLI(['file'=>'classes/Style/StylesheetBuilder.php']);

			$root = $this->emptyScratchDirectory() . '/';
			$css = $root . 'css/';
			mkdir($css . 'components', 0755, TRUE);

			foreach(['layers.css', 'fonts.css', 'reset.css', 'tokens.css', 'base.css', 'legacy.css'] as $source) {
				file_put_contents($css . $source, '/* ' . $source . ' */ .' . basename($source, '.css') . ' { color: red; }');
			}

			file_put_contents($css . 'components/20-b.css', '.b { color: blue; }');
			file_put_contents($css . 'components/10-a.css', '.a { color: green; }');

			foreach($args['themes'] as $site=>$theme) {
				mkdir($root . 'templates/' . $site, 0755, TRUE);
				file_put_contents($root . 'templates/' . $site . '/theme.css', $theme);
			}

			return new StylesheetBuilder([
				'enginecssdir'=>$css,
				'templatedirs'=>[$root . 'templates/'],
				'outputdir'=>$root . 'build/',
				'quiet'=>TRUE,
			]);
		}

		public function testBuild() {
			$builder = $this->newBuilder(['themes'=>['somesite'=>':root { --accent: #BF1F2A; }']]);
			$manifest = $builder->Build();

			$this->assertSame(['default', 'somesite'], array_keys($manifest), 'every site gets a stylesheet; a themed one its own');
			$this->assertMatchesRegularExpression('/^somesite\.[0-9a-f]{10}\.css$/', $manifest['somesite'], 'named by its content');

			$default = file_get_contents($builder->output_dir . $manifest['default']);
			$themed = file_get_contents($builder->output_dir . $manifest['somesite']);

			$this->assertStringNotContainsString('/*', $default, 'comments are stripped');
			$this->assertLessThan(strpos($default, '.b {'), strpos($default, '.a {'), 'components in name order');
			$this->assertStringContainsString('@layer components {', $default);
			$this->assertStringContainsString('@layer site {', $themed, 'the theme is wrapped in the site layer');
			$this->assertStringNotContainsString('@layer site', $default);

			$this->assertSame($manifest, json_decode(file_get_contents($builder->output_dir . 'stylesheets.json'), TRUE));

			$this->assertSame($manifest, $builder->Build(), 'the same sources build the same names');
		}

			/*
				A cached page names the stylesheet it was rendered with, so an
				old build is kept until it is a month old.
			*/

		public function testRemoveOldBuilds() {
			$builder = $this->newBuilder(['themes'=>[]]);
			$manifest = $builder->Build();

			file_put_contents($builder->output_dir . 'default.0000000000.css', 'old');
			file_put_contents($builder->output_dir . 'default.1111111111.css', 'older');
			touch($builder->output_dir . 'default.1111111111.css', time() - 40 * 24 * 60 * 60);

			$builder->Build();

			$this->assertFileExists($builder->output_dir . 'default.0000000000.css', 'a recent old build stays');
			$this->assertFileDoesNotExist($builder->output_dir . 'default.1111111111.css', 'a month-old one goes');
			$this->assertFileExists($builder->output_dir . $manifest['default'], 'the current one stays');
		}
	}

?>
