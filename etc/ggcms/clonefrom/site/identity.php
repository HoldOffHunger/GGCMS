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