<?php

	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/header.php');
	require_once(GGCMS_DIR . 'modules/html/list/hideable-arrow.php');

		/*
			One of master-c's panels: a third of the page across, framed,
			its heading between two of its pictures, over a scrolling list
			of links that open and close on their arrows -- module_hideable
			from list/hideable-arrow.php.

			The links are data, a file a panel, in
			modules/html/data/master-c-<menu>.php, and each file returns its
			list.  They are required from Menu(), so a link that names the
			host reads it from $this->that->domain_object.

			Switches:
			  menu        which panel's links: the file's name after
			              master-c-
			  title, image, divmouseover, imagemouseover
			              the heading, as module_header takes them
			  textclass   the heading text's classes, when a panel's are
			              not the others'
		*/

	class module_mastercpanel extends module_spacing {
		public $that;
		public $menu;
		public $title;
		public $image;
		public $div_mouseover;
		public $image_mouseover;
		public $text_class;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->menu = $args['menu'];
			$this->title = $args['title'];
			$this->image = $args['image'];
			$this->div_mouseover = $args['divmouseover'];
			$this->image_mouseover = $args['imagemouseover'];
			$this->text_class = $args['textclass'] ?? 'margin-0px padding-0px vertical-center padding-top-14px';
		}

		public function Display() {
			$divider = new module_divider;

			$divider->displaystart(['class'=>'width-33percent float-left']);
			$divider->displaystart(['class'=>'margin-top-5px margin-right-5px margin-left-5px padding-5px margin-bottom-2px']);
			$divider->displaystart(['class'=>'width-100percent border-2px']);

			$this->DisplayHeader();

			$divider->displaystart(['class'=>'width-100percent height-400px overflow-auto']);
			$divider->displaystart(['class'=>'margin-5px padding-5px']);

			$this->DisplayMenu();

			for($i = 0; $i < 5; $i++) {
				$divider->displayend([]);
			}

			return TRUE;
		}

		public function DisplayHeader() {
			$header = new module_header;

			$header->display([
				'title'=>$this->title,
				'image'=>$this->image,
				'divmouseover'=>$this->div_mouseover,
				'imagemouseover'=>$this->image_mouseover,
				'level'=>2,
				'divclass'=>'horizontal-center width-100percent border-bottom-1px background-color-gray13',
				'textclass'=>$this->text_class,
				'imagedivclass'=>'background-color-gray10 margin-5px',
				'imageclass'=>'border-1px width-50px',
				'domainobject'=>$this->that->domain_object,
				'leftimageenable'=>1,
				'rightimageenable'=>1,
			]);

			return TRUE;
		}

		public function DisplayMenu() {
			$hideable = new module_hideable;

			$hideable->display([
				'options'=>[
					'domainobject'=>$this->that->domain_object,
				],
				'list'=>$this->Menu(),
			]);

			return TRUE;
		}

		public function Menu() {
			return require(GGCMS_DIR . 'modules/html/data/master-c-' . $this->menu . '.php');
		}
	}

?>
