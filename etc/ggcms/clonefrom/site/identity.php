<?php

	class AbstractGlobals_Site_identity {

			/*
				Defaults are deliberately empty rather than invented.  An empty
				answer renders nothing, which is what these readers already do;
				a placeholder would ship a lie inside every EPub instead.

				A site fills these in at etc/ggcms/<reverse-dns>/site/identity.php
				in a class named AbstractGlobals_Site_identity_override.
			*/

				// Dublin Core
				// -----------------------------------------------

		public function Publisher() {
			return '';
		}

		public function Creator() {
			return '';
		}

		public function Contributor() {
			return '';
		}

		public function Rights() {
			return '';
		}

		public function Copyright() {
			return '';
		}

			/*
				The address the site answers on, for the contact and reply-to
				meta tags every page carries.  AbstractGlobals_contact has its
				own GetEmailContact() for the contact page's body; this is the
				site-wide one, because the meta tags are on every script.
			*/

		public function Contact() {
			return '';
		}

				// Languages
				// -----------------------------------------------

			/*
				The language a page is in when nobody asks for another, and every
				language the site offers.  A request for any other language gets
				the plain page, so a site offering one language has one cacheable
				page per entry instead of thirteen.  A site that is not
				multilingual says so with ['en'].
			*/

		public function DefaultLanguage() {
			return 'en';
		}

		public function SupportedLanguages() {
			return [
				'de',
				'en',
				'es',
				'fr',
				'it',
				'ja',
				'nl',
				'pl',
				'pt',
				'ru',
				'tr',
				'zh',
			];
		}

				// Readiness
				// -----------------------------------------------

			/*
				Both were flags in PrimaryHostRecord that read as NULL for years,
				and !NULL is TRUE, so every site has been offering languages and
				search whether or not it was ready to.  FALSE here preserves that
				behaviour exactly; a site that is genuinely not ready says so.
			*/

		public function NotReadyForLanguages() {
			return FALSE;
		}

		public function NotReadyForSearch() {
			return FALSE;
		}

			/*
				Whether pages link to their alternate formats -- view.pdf,
				view.rdf and the rest -- in the head.  A site that does not
				serve those formats says FALSE, or every page hands crawlers
				links that only redirect.
			*/

		public function ShowAlternateFormats() {
			return TRUE;
		}

			/*
				Whether an entry with nothing under it -- a single quote, a
				single link -- comes in those formats too.  A document carries
				an entry's children after the entry itself, so a collection's
				PDF already holds every quote in it.  FALSE keeps such an entry
				to the web page and its on-screen editions, and a request for
				one of its documents goes to its parent's.

				TRUE for a site whose single entries are texts in their own
				right, books and essays.
			*/

		public function ShowAlternateFormatsOnLeaves() {
			return TRUE;
		}

				// Infrastructure
				// -----------------------------------------------

			/*
				Other domains answering for this site, named in robots.txt.  An
				array, sorted by the caller.
			*/

		public function AlternateDomain() {
			return [];
		}
	}

?>