<?php

	/*
		Image serves what is under a site's /image/.  The site is pointed at
		this test's scratch directory, where the files are made.
	*/

	class ImageTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Format/Base/AbstractBaseFormat.php']);
			$this->requireEngine(['file'=>'classes/Format/Image.php']);
		}

			/*
				On 27 September 2026 every .webp and .jfif on the sites -- 410
				of them -- came back an empty 200: the MIME table had never
				heard of either.  A query string made any image do the same.
				And .html, .svg and .php under /image/ were served as what they
				are, so an upload with such a name was a page on the domain.
			*/

		public function testImageRequest() {
			$directory = $this->emptyScratchDirectory() . '/www/image/j/6/';
			mkdir($directory, 0755, TRUE);
			foreach(['photo.webp', 'photo.jfif', 'photo.jpg', 'Burn Shit_ Image.jpg', 'page.html', 'icon.svg', 'master-c.php'] as $file) {
				file_put_contents($directory . $file, 'x');
			}

			$image = new Image(['handler'=>(object)['domain'=>(object)['primary_domain_lowercased'=>'tests/ImageTest']]]);

			$this->assertSame('image/webp', $image->ImageRequest(['requesturi'=>'/image/j/6/photo.webp'])['mimetype']);
			$this->assertSame('image/jpeg', $image->ImageRequest(['requesturi'=>'/image/j/6/photo.jfif'])['mimetype']);
			$this->assertSame('image/jpeg', $image->ImageRequest(['requesturi'=>'/image/j/6/photo.jpg?stopredirect=1'])['mimetype'], 'a query string is not part of the name');
			$this->assertSame($directory . 'photo.jpg', $image->ImageRequest(['requesturi'=>'/image/j/6/photo.jpg'])['location']);

			foreach(['/image/j/6/Burn%20Shit_%20Image.jpg', '/image/j/6/Burn+Shit_+Image.jpg'] as $spaced) {
				$this->assertSame('Burn Shit_ Image.jpg', $image->ImageRequest(['requesturi'=>$spaced])['filename'], 'spaces, as either encoding writes them: ' . $spaced);
			}

			foreach(['page.html', 'icon.svg', 'master-c.php', 'missing.jpg'] as $refused) {
				$this->assertFalse($image->ImageRequest(['requesturi'=>'/image/j/6/' . $refused]), $refused . ' ends in the image 404');
			}
		}
	}

?>
