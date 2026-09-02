<?php

	class AbstractGlobals_RecordRelations_enabled {

		/*
			Which of the expensive relational walks a site actually renders.

			These are not cheap lookups.  The grandchild tier walks three
			levels of the entry graph and, before it was batched, was most of
			the query count in a front-page render; siblings fetch a parent's
			whole child list on every view.  A site that never prints them was
			still paying for all of it.

			Defaults are FALSE, and a site that renders one of these switches
			it on at etc/ggcms/<reverse-dns>/record_relations/enabled.php in a
			class named AbstractGlobals_RecordRelations_enabled_override.

			Whether a switch belongs on is answered by the site's own template
			set: if it includes the module that prints the data, it needs the
			data.  entry-children-grandchildren prints the grandchild tier, and
			entry-navigation prints siblings.

			The failure mode of getting it wrong is visible rather than silent
			-- a navigation block or a grandchild list stops appearing -- and
			the repair is one file.  That is deliberately the opposite of the
			child_types fault, where a default of FALSE combined with an
			override path that never resolved and content went missing
			everywhere with nothing to point at.  Absent config here means
			every relation is ON, so an install that has not written this file
			behaves exactly as the engine did before these switches existed.
		*/

				// The grandchild tier, built only on a main page
				// -----------------------------------------------

			/*
				Each grandchild's own children -- for revoltlib, the chapters
				hanging under a book under an author.
			*/

		public function GrandChildRecords_enabled() {
			return FALSE;
		}

			/*
				Associations attached to each grandchild, and their parents.
			*/

		public function GrandChildAssociations_enabled() {
			return FALSE;
		}

			/*
				The most recently added children, for a front page that lists
				what is new.
			*/

		public function NewestChildren_enabled() {
			return FALSE;
		}

				// Navigation
				// -----------------------------------------------

			/*
				The entries either side of this one under the same parent, which
				is what entry-navigation prints as next and previous.
			*/

		public function Siblings_enabled() {
			return FALSE;
		}
	}

?>