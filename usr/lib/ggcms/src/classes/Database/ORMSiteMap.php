<?php

	class ORMSiteMap {
		public $dbaccessobject;
		
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			$this->dbaccessobject = $args['dbaccessobject'];
		}
		
		/*
		
			SELECT
			
			E1.Code as E1_Code,
			E2.Code as E2_Code,
			E3.Code as E3_Code,
			E4.Code as E4_Code,
			E5.Code as E5_Code,
			E6.Code as E6_Code,
			E7.Code as E7_Code
			
			FROM Assignment as A1
			
			JOIN Entry E1 ON A1.Childid = 0 and A1.Parentid = E1.id
			
			LEFT JOIN Assignment A2 ON A2.Parentid = E1.id
			LEFT JOIN Entry E2 ON A2.Childid = E2.id
			
			LEFT JOIN Assignment A3 ON A3.Parentid = E2.id
			LEFT JOIN Entry E3 ON A3.Childid = E3.id
			
			LEFT JOIN Assignment A4 ON A4.Parentid = E3.id
			LEFT JOIN Entry E4 ON A4.Childid = E4.id
			
			LEFT JOIN Assignment A5 ON A5.Parentid = E4.id
			LEFT JOIN Entry E5 ON A5.Childid = E5.id
			
			LEFT JOIN Assignment A6 ON A6.Parentid = E5.id
			LEFT JOIN Entry E6 ON A6.Childid = E6.id
			
			LEFT JOIN Assignment A7 ON A7.Parentid = E6.id
			LEFT JOIN Entry E7 ON A7.Childid = E7.id
		
		*/
		
		public function GetEntrySiteMapCodes($args) {
			$sql = 'SELECT ';
			
			$sql .= 'E1.Code as E1_Code, E1.Title as E1_Title, E1.Subtitle as E1_Subtitle, E1.ListTitle as E1_ListTitle, E1.LastModificationDate as E1_LastModificationDate, ';
			$sql .= 'E2.Code as E2_Code, E2.Title as E2_Title, E2.Subtitle as E2_Subtitle, E2.ListTitle as E2_ListTitle, E2.LastModificationDate as E2_LastModificationDate, ';
			$sql .= 'E3.Code as E3_Code, E3.Title as E3_Title, E3.Subtitle as E3_Subtitle, E3.ListTitle as E3_ListTitle, E3.LastModificationDate as E3_LastModificationDate, ';
			$sql .= 'E4.Code as E4_Code, E4.Title as E4_Title, E4.Subtitle as E4_Subtitle, E4.ListTitle as E4_ListTitle, E4.LastModificationDate as E4_LastModificationDate, ';
			$sql .= 'E5.Code as E5_Code, E5.Title as E5_Title, E5.Subtitle as E5_Subtitle, E5.ListTitle as E5_ListTitle, E5.LastModificationDate as E5_LastModificationDate, ';
			$sql .= 'E6.Code as E6_Code, E6.Title as E6_Title, E6.Subtitle as E6_Subtitle, E6.ListTitle as E6_ListTitle, E6.LastModificationDate as E6_LastModificationDate, ';
			$sql .= 'E7.Code as E7_Code, E7.Title as E7_Title, E7.Subtitle as E7_Subtitle, E7.ListTitle as E7_ListTitle, E7.LastModificationDate as E7_LastModificationDate ';
			
				/*
					Publish = 1 on every level, not only the first three.

					E1, E2 and E3 carried the filter and E4 through E7 did not, so
					an unpublished entry four levels down was listed in the public
					sitemap and handed to search engines.  It reads like the filter
					was added from the top and stopped partway.

					In the ON clause rather than the WHERE clause, which is the
					difference between hiding an entry and hiding its ancestors.
					A LEFT JOIN that fails leaves E4 null and keeps the row, so the
					URL falls back to the deepest published level -- /a/b/c/ where
					it would have been /a/b/c/d/.  The same condition in WHERE
					would drop the row entirely and take the published parents
					with it.  E3 already did it this way; the rest follow it.
				*/

			$sql .= 'FROM Assignment as A1 ';
			
			$sql .= 'JOIN Entry E1 ON A1.Childid = 0 and A1.Parentid = E1.id AND E1.Publish = 1 ';
			
			$sql .= 'JOIN Assignment A2 ON A2.Parentid = E1.id ';
			$sql .= 'JOIN Entry E2 ON A2.Childid = E2.id AND E2.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A3 ON A3.Parentid = E2.id ';
			$sql .= 'LEFT JOIN Entry E3 ON A3.Childid = E3.id AND E3.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A4 ON A4.Parentid = E3.id ';
			$sql .= 'LEFT JOIN Entry E4 ON A4.Childid = E4.id AND E4.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A5 ON A5.Parentid = E4.id ';
			$sql .= 'LEFT JOIN Entry E5 ON A5.Childid = E5.id AND E5.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A6 ON A6.Parentid = E5.id ';
			$sql .= 'LEFT JOIN Entry E6 ON A6.Childid = E6.id AND E6.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A7 ON A7.Parentid = E6.id ';
			$sql .= 'LEFT JOIN Entry E7 ON A7.Childid = E7.id AND E7.Publish = 1 ';
			
			$page = $args['page'];
			$sqlbindstring = '';
			$bindings = [];
			
			if($page) {
				$sql .= 'WHERE E2.Code = ? ';
				$sqlbindstring .= 's';
				$bindings[] = $page;
			}

				/*
					perpage was being passed in and never reaching the SQL, so a
					single request built every URL a site has -- 11,617 of them on
					revoltlib, 50 MB in one page, which is what the kill switch in
					sitemap.php exists to prevent.

					Cast rather than bound: LIMIT will not take a bound string, and
					an int cast cannot carry anything but a number into the query.
				*/

				/*
					Without an ORDER BY, LIMIT and OFFSET slice an unordered result,
					so MySQL is free to return the rows in a different sequence for
					each page.  Tested on revoltlib's 11,617 anarchism URLs: 13 of
					them appeared in two parts, and by the same mechanism others
					would appear in none.

					Shallowest first, which is priority order -- a row with fewer
					levels filled is nearer the top of the tree and matters more --
					then by code, which makes the sequence total and repeatable.
				*/

			$sql .= 'ORDER BY ';
			$sql .= '(E2.Code IS NOT NULL) + (E3.Code IS NOT NULL) + (E4.Code IS NOT NULL) + ';
			$sql .= '(E5.Code IS NOT NULL) + (E6.Code IS NOT NULL) + (E7.Code IS NOT NULL) ASC, ';
			$sql .= 'E2.Code ASC, E3.Code ASC, E4.Code ASC, E5.Code ASC, E6.Code ASC, E7.Code ASC ';

			if($args['perpage']) {
				$sql .= 'LIMIT ' . (int) $args['perpage'] . ' ';

					//  Part 1 is the first thousand, part 2 the next, and so on, so
					//  every URL is reachable through some part rather than the
					//  first thousand being the only ones a crawler ever sees.

				if($args['part'] > 1) {
					$sql .= 'OFFSET ' . ((int) $args['part'] - 1) * (int) $args['perpage'] . ' ';
				}
			}
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>$sqlbindstring,
				'recordvalues'=>$bindings,
			];
			
			$codes = $this->dbaccessobject->FillArraysFromDB($fill_arrays_from_db_args);
			
			return $codes;
		}
		
		public function GetEntrySiteMapCodeCount() {
			$sql = 'SELECT COUNT(A1.id) AS EntryCount ';
			
				/*
					The same joins as GetEntrySiteMapCodes(), and they have to stay
					the same joins.  This count decides how many parts the sitemap
					is split into, so counting rows the listing will not produce
					leaves the last parts short or empty.  It filtered nothing at
					any level while the listing filtered three, so the two already
					disagreed before the four missing ones were added.
				*/

			$sql .= 'FROM Assignment as A1 ';
			
			$sql .= 'JOIN Entry E1 ON A1.Childid = 0 and A1.Parentid = E1.id AND E1.Publish = 1 ';
			
			$sql .= 'JOIN Assignment A2 ON A2.Parentid = E1.id ';
			$sql .= 'JOIN Entry E2 ON A2.Childid = E2.id AND E2.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A3 ON A3.Parentid = E2.id ';
			$sql .= 'LEFT JOIN Entry E3 ON A3.Childid = E3.id AND E3.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A4 ON A4.Parentid = E3.id ';
			$sql .= 'LEFT JOIN Entry E4 ON A4.Childid = E4.id AND E4.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A5 ON A5.Parentid = E4.id ';
			$sql .= 'LEFT JOIN Entry E5 ON A5.Childid = E5.id AND E5.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A6 ON A6.Parentid = E5.id ';
			$sql .= 'LEFT JOIN Entry E6 ON A6.Childid = E6.id AND E6.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A7 ON A7.Parentid = E6.id ';
			$sql .= 'LEFT JOIN Entry E7 ON A7.Childid = E7.id AND E7.Publish = 1 ';
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>'',
				'recordvalues'=>[],
			];
			
			$count = $this->dbaccessobject->FillArraysFromDB($fill_arrays_from_db_args)[0]['EntryCount'];
			
			return $count;
		}
		
		public function GetSitemapPages($args) {
			$sql = 'SELECT ';
			
			/*
			$sql .= 'E1.Code as E1_Code, E1.Title as E1_Title, E1.Subtitle as E1_Subtitle, E1.ListTitle as E1_ListTitle, E1.LastModificationDate as E1_LastModificationDate, ';
			$sql .= 'E2.Code as E2_Code, E2.Title as E2_Title, E2.Subtitle as E2_Subtitle, E2.ListTitle as E2_ListTitle, E2.LastModificationDate as E2_LastModificationDate, ';
			$sql .= 'E3.Code as E3_Code, E3.Title as E3_Title, E3.Subtitle as E3_Subtitle, E3.ListTitle as E3_ListTitle, E3.LastModificationDate as E3_LastModificationDate, ';
			$sql .= 'E4.Code as E4_Code, E4.Title as E4_Title, E4.Subtitle as E4_Subtitle, E4.ListTitle as E4_ListTitle, E4.LastModificationDate as E4_LastModificationDate, ';
			$sql .= 'E5.Code as E5_Code, E5.Title as E5_Title, E5.Subtitle as E5_Subtitle, E5.ListTitle as E5_ListTitle, E5.LastModificationDate as E5_LastModificationDate, ';
			$sql .= 'E6.Code as E6_Code, E6.Title as E6_Title, E6.Subtitle as E6_Subtitle, E6.ListTitle as E6_ListTitle, E6.LastModificationDate as E6_LastModificationDate, ';
			$sql .= 'E7.Code as E7_Code, E7.Title as E7_Title, E7.Subtitle as E7_Subtitle, E7.ListTitle as E7_ListTitle, E7.LastModificationDate as E7_LastModificationDate ';
			*/
			
			$sql .= 'E2.Title, E2.Code, COUNT(*) as EntryCount, MAX(GREATEST(';	// I am MAX GREATEST!!!
			
			$sql .= 'IFNULL(E2.LastModificationDate, "1000-01-01"), ';
			$sql .= 'IFNULL(E3.LastModificationDate, "1000-01-01"), ';
			$sql .= 'IFNULL(E4.LastModificationDate, "1000-01-01"), ';
			$sql .= 'IFNULL(E5.LastModificationDate, "1000-01-01"), ';
			$sql .= 'IFNULL(E6.LastModificationDate, "1000-01-01"), ';
			$sql .= 'IFNULL(E7.LastModificationDate, "1000-01-01")';
			
			$sql .= ')) as LastModificationDate ';
			
				/*
					Filtered to match the other two.  This builds the index of
					sitemap parts -- one row per section, with a count and a last
					modification date -- and an unfiltered count advertises more
					URLs in a section than the section will produce, while an
					unfiltered GREATEST() dates a section by an unpublished draft
					nobody can read.
				*/

			$sql .= 'FROM Assignment as A1 ';
			
			$sql .= 'JOIN Entry E1 ON A1.Childid = 0 and A1.Parentid = E1.id AND E1.Publish = 1 ';
			
			$sql .= 'JOIN Assignment A2 ON A2.Parentid = E1.id ';
			$sql .= 'JOIN Entry E2 ON A2.Childid = E2.id AND E2.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A3 ON A3.Parentid = E2.id ';
			$sql .= 'LEFT JOIN Entry E3 ON A3.Childid = E3.id AND E3.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A4 ON A4.Parentid = E3.id ';
			$sql .= 'LEFT JOIN Entry E4 ON A4.Childid = E4.id AND E4.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A5 ON A5.Parentid = E4.id ';
			$sql .= 'LEFT JOIN Entry E5 ON A5.Childid = E5.id AND E5.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A6 ON A6.Parentid = E5.id ';
			$sql .= 'LEFT JOIN Entry E6 ON A6.Childid = E6.id AND E6.Publish = 1 ';
			
			$sql .= 'LEFT JOIN Assignment A7 ON A7.Parentid = E6.id ';
			$sql .= 'LEFT JOIN Entry E7 ON A7.Childid = E7.id AND E7.Publish = 1 ';
			
			$sql .= 'GROUP BY E2.Code ORDER BY E2.Code, LastModificationDate ASC ';
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>'',
				'recordvalues'=>[],
			];
			
			$codes = $this->dbaccessobject->FillArraysFromDB($fill_arrays_from_db_args);
			
			return $codes;
		}
	}

?>