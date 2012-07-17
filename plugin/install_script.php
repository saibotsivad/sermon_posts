<?php
if ( !current_user_can( "activate_plugins" ) ) die();


// if this scripts db version is higher than the installed version (or if none is
// installed) then we will run the install
$this_db_version = "0.17";
$installed_db_version = get_option( "plugin_tlsp_dbversion", "0.0" );
if ( version_compare($this_db_version, $installed_db_version, ">") ) :
// ===== install script =====

update_option( "plugin_tlsp_dbversion", "0.17" );

/*
	While storing the Bible as raw MySQL data would make the insert more efficient, I chose this
	route because it is easier to view verse counts and correct them, if needed.
	The Old Testament verse counts come from:
		http://catholic-resources.org/Bible/OT-Statistics-NAB.htm
	The New Testament verse counts come from:
		http://catholic-resources.org/Bible/NT-Statistics-Greek.htm
	Where verse counts differ between translations (specifically in Acts 10, 19, 2 Cor. 13,
	3 John) the higher number was chosen to retain greater database accuracy. No theological
	point was intended :)
*/

// The Bible, in an array, which is fed into the database.
$books = array(
	'Genesis' => array( 31, 25, 24, 26, 32, 22, 24, 22, 29, 32, 32, 20, 18, 24, 21, 16, 27, 33, 38, 18, 34, 24, 20, 67, 34, 35, 46, 22, 35, 43, 54, 33, 20, 31, 29, 43, 36, 30, 23, 23, 57, 38, 34, 34, 28, 34, 31, 22, 33, 26 ), 
	'Exodus' => array( 22, 25, 22, 31, 23, 30, 29, 28, 35, 29, 10, 51, 22, 31, 27, 36, 16, 27, 25, 26, 37, 30, 33, 18, 40, 37, 21, 43, 46, 38, 18, 35, 23, 35, 35, 38, 29, 31, 43, 38 ),
	'Leviticus' => array( 17, 16, 17, 35, 26, 23, 38, 36, 24, 20, 47, 8, 59, 57, 33, 34, 16, 30, 37, 27, 24, 33, 44, 23, 55, 46, 34 ),
	'Numbers' => array( 54, 34, 51, 49, 31, 27, 89, 26, 23, 36, 35, 16, 33, 45, 41, 35, 28, 32, 22, 29, 35, 41, 30, 25, 19, 65, 23, 31, 39, 17, 54, 42, 56, 29, 34, 13 ),
	'Deuteronomy' => array( 46, 37, 29, 49, 33, 25, 26, 20, 29, 22, 32, 31, 19, 29, 23, 22, 20, 22, 21, 20, 23, 29, 26, 22, 19, 19, 26, 69, 28, 20, 30, 52, 29, 12 ),
	'Joshua' => array( 18, 24, 17, 24, 15, 27, 26, 35, 27, 43, 23, 24, 33, 15, 63, 10, 18, 28, 51, 9, 45, 34, 16, 33 ),
	'Judges' => array( 36, 23, 31, 24, 31, 40, 25, 35, 57, 18, 40, 15, 25, 20, 20, 31, 13, 31, 30, 48, 25 ),
	'Ruth' => array( 22, 23, 18, 22 ),
	'1 Samuel' => array( 28, 36, 21, 22, 12, 21, 17, 22, 27, 27, 15, 25, 23, 52, 35, 23, 58, 30, 24, 42, 16, 23, 28, 23, 43, 25, 12, 25, 11, 31, 13 ),
	'2 Samuel' => array( 27, 32, 39, 12, 25, 23, 29, 18, 13, 19, 27, 31, 39, 33, 37, 23, 29, 32, 44, 26, 22, 51, 39, 25 ),
	'1 Kings' => array( 53, 46, 28, 20, 32, 38, 51, 66, 28, 29, 43, 33, 34, 31, 34, 34, 24, 46, 21, 43, 29, 54 ),
	'2 Kings' => array( 18, 25, 27, 44, 27, 33, 20, 29, 37, 36, 20, 22, 25, 29, 38, 20, 41, 37, 37, 21, 26, 20, 37, 20, 30 ),
	'1 Chronicles' => array( 54, 55, 24, 43, 41, 66, 40, 40, 44, 14, 47, 41, 14, 17, 29, 43, 27, 17, 19, 8, 30, 19, 32, 31, 31, 32, 34, 21, 30 ),
	'2 Chronicles' => array( 18, 17, 17, 22, 14, 42, 22, 18, 31, 19, 23, 16, 23, 14, 19, 14, 19, 34, 11, 37, 20, 12, 21, 27, 28, 23, 9, 27, 36, 27, 21, 33, 25, 33, 26, 23 ),
	'Ezra' => array( 11, 70, 13, 24, 17, 22, 28, 36, 15, 44 ),
	'Nehemiah' => array( 11, 20, 38, 17, 19, 19, 72, 18, 37, 40, 36, 47, 31 ),
	'Esther' => array( 22, 23, 15, 17, 14, 14, 10, 17, 32, 3, 17, 8, 30, 16, 24, 10 ),
	'Job' => array( 22, 13, 26, 21, 27, 30, 21, 22, 35, 22, 20, 25, 28, 22, 35, 22, 16, 21, 29, 29, 34, 30, 17, 25, 6, 14, 21, 28, 25, 31, 40, 22, 33, 37, 16, 33, 24, 41, 30, 32, 26, 17 ),
	'Psalms' => array( 6, 12, 9, 9, 13, 11, 18, 10, 21, 18, 7, 9, 6, 7, 5, 11, 15, 51, 15, 10, 14, 32, 6, 10, 22, 11, 14, 9, 11, 13, 25, 11, 22, 23, 28, 13, 40, 23, 14, 18, 14, 12, 5, 27, 18, 12, 10, 15, 21, 23, 21, 11, 7, 9, 24, 14, 12, 12, 18, 14, 9, 13, 12, 11, 14, 20, 8, 36, 37, 6, 24, 20, 28, 23, 11, 13, 21, 72, 13, 20, 17, 8, 19, 13, 14, 17, 7, 19, 53, 17, 16, 16, 5, 23, 11, 13, 12, 9, 9, 5, 8, 29, 22, 35, 45, 48, 43, 14, 31, 7, 10, 10, 9, 8, 18, 19, 2, 29, 176, 7, 8, 9, 4, 8, 5, 6, 5, 6, 8, 8, 3, 18, 3, 3, 21, 26, 9, 8, 24, 14, 10, 8, 12, 15, 21, 10, 20, 14, 9, 6 ),
	'Proverbs' => array( 33, 22, 35, 27, 23, 35, 27, 36, 18, 32, 31, 28, 25, 35, 33, 33, 28, 24, 29, 30, 31, 29, 35, 34, 28, 28, 27, 28, 27, 33, 31 ),
	'Ecclesiastes' => array( 18, 26, 22, 17, 20, 12, 29, 17, 18, 20, 10, 14 ),
	'Song of Solomon' => array( 17, 17, 11, 16, 16, 12, 14, 14 ),
	'Isaiah' => array( 31, 22, 26, 6, 30, 13, 25, 23, 20, 34, 16, 6, 22, 32, 9, 14, 14, 7, 25, 6, 17, 25, 18, 23, 12, 21, 13, 29, 24, 33, 9, 20, 24, 17, 10, 22, 38, 22, 8, 31, 29, 25, 28, 28, 25, 13, 15, 22, 26, 11, 23, 15, 12, 17, 13, 12, 21, 14, 21, 22, 11, 12, 19, 11, 25, 24 ),
	'Jeremiah' => array( 19, 37, 25, 31, 31, 30, 34, 23, 25, 25, 23, 17, 27, 22, 21, 21, 27, 23, 15, 18, 14, 30, 40, 10, 38, 24, 22, 17, 32, 24, 40, 44, 26, 22, 19, 32, 21, 28, 18, 16, 18, 22, 13, 30, 5, 28, 7, 47, 39, 46, 64, 34 ),
	'Lamentations' => array( 22, 22, 66, 22, 22 ),
	'Ezekial' => array( 28, 10, 27, 17, 17, 14, 27, 18, 11, 22, 25, 28, 23, 23, 8, 63, 24, 32, 14, 44, 37, 31, 49, 27, 17, 21, 36, 26, 21, 26, 18, 32, 33, 31, 15, 38, 28, 23, 29, 49, 26, 20, 27, 31, 25, 24, 23, 35 ),
	'Daniel' => array( 21, 49, 100, 34, 30, 29, 28, 27, 27, 21, 45, 13, 64, 42 ),
	'Hosea' => array( 9, 25, 5, 19, 15, 11, 16, 14, 17, 15, 11, 15, 15, 10 ),
	'Joel' => array( 20, 27, 5, 21 ),
	'Amos' => array( 15, 16, 15, 13, 27, 14, 17, 14, 15 ),
	'Obadiah' => array( 21 ),
	'Jonah' => array( 17, 11, 10, 11 ),
	'Micah' => array( 16, 13, 12, 14, 14, 16, 20 ),
	'Nahum' => array( 14, 14, 19 ),
	'Habakkuk' => array( 17, 20, 19 ),
	'Zephaniah' => array( 18, 15, 20 ),
	'Haggai' => array( 15, 23 ),
	'Zechariah' => array( 17, 17, 10, 14, 11, 15, 14, 23, 17, 12, 17, 14, 9, 21 ),
	'Malachi' => array( 14, 17, 24 ),
	'Matthew' => array( 25, 23, 17, 25, 48, 34, 29, 34, 38, 42, 30, 50, 58, 36, 39, 28, 27, 35, 30, 34, 46, 46, 39, 51, 46, 75, 66, 20 ),
	'Mark' => array( 45, 28, 35, 41, 43, 56, 37, 38, 50, 52, 33, 44, 37, 72, 47, 20 ),
	'Luke' => array( 80, 52, 38, 44, 39, 49, 50, 56, 62, 42, 54, 59, 35, 35, 32, 31, 37, 43, 48, 47, 38, 71, 56, 53 ),
	'John' => array( 51, 25, 36, 54, 47, 71, 53, 59, 41, 42, 57, 50, 38, 31, 27, 33, 26, 40, 42, 31, 25 ),
	'Acts' => array( 26, 47, 26, 37, 42, 15, 60, 40, 43, 49, 30, 25, 52, 28, 41, 40, 34, 28, 41, 38, 40, 30, 35, 27, 27, 32, 44, 31 ),
	'Romans' => array( 32, 29, 31, 25, 21, 23, 25, 39, 33, 21, 36, 21, 14, 23, 33, 27 ),
	'1 Corinthians' => array( 31, 16, 23, 21, 13, 20, 40, 13, 27, 33, 34, 31, 13, 40, 58, 24 ),
	'2 Corinthians' => array( 24, 17, 18, 18, 21, 18, 16, 24, 15, 18, 33, 21, 14 ),
	'Galatians' => array( 24, 21, 29, 31, 26, 18 ),
	'Ephesians' => array( 23, 22, 21, 32, 33, 24 ),
	'Philippians' => array( 30, 30, 21, 23 ),
	'Colossians' => array( 29, 23, 25, 18 ),
	'1 Thessalonians' => array( 10, 20, 13, 18, 28 ),
	'2 Thessalonians' => array( 12, 17, 18 ),
	'1 Timothy' => array( 20, 15, 16, 16, 25, 21 ),
	'2 Timothy' => array( 18, 26, 17, 22 ),
	'Titus' => array( 16, 15, 15 ),
	'Philemon' => array( 25 ),
	'Hebrews' => array( 14, 18, 19, 16, 14, 20, 28, 13, 28, 39, 40, 29, 25 ),
	'James' => array( 27, 26, 18, 17, 20 ),
	'1 Peter' => array( 25, 25, 22, 19, 14 ),
	'2 Peter' => array( 21, 22, 18 ),
	'1 John' => array( 10, 29, 24, 21, 21 ),
	'2 John' => array( 13 ),
	'3 John' => array( 15 ),
	'Jude' => array( 25 ),
	'Revelation' => array( 20, 29, 22, 11, 14, 17, 17, 13, 21, 11, 19, 17, 18, 20, 8, 21, 18, 24, 21, 15, 27, 21 )
);

// wordpress database class
global $wpdb;

// wordpress database helper
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// table names
$table_sermon = $wpdb->prefix . "tlsp_reference";
$table_thebible = $wpdb->prefix . "tlsp_bible";
$table_biblebook = $wpdb->prefix . "tlsp_book";

// the sql queries are required to be in a particular format for the wordpress database helper to work

// create the sermon ranges table
$sql = "CREATE TABLE IF NOT EXISTS `{$table_sermon}` (
`id` INT NOT NULL AUTO_INCREMENT,
`sermon` INT NOT NULL,
`start` INT NOT NULL,
`end` INT NOT NULL,
PRIMARY KEY(`id`),
UNIQUE KEY `sermon` ( `sermon`, `start`, `end` )
);";
dbDelta($sql);

// create the bible verse id table
$sql = "CREATE TABLE IF NOT EXISTS `{$table_thebible}` (
`id` INT NOT NULL,
`book` INT NOT NULL,
`chapter` INT NOT NULL,
`verse` INT NOT NULL,
PRIMARY KEY( `id`, `book`, `chapter`, `verse` )
);";
dbDelta($sql);

// create the bible book ids
$sql = "CREATE TABLE IF NOT EXISTS `{$table_biblebook}` (
`id` INT NOT NULL,
`name` VARCHAR(18) NOT NULL,
PRIMARY KEY( `id`, `name` )
);";
dbDelta($sql);

// get the mysql maximum allowed packet size
$maxsize = $wpdb->get_results( "SHOW VARIABLES LIKE 'max_allowed_packet'" );
$maxsize = $maxsize[0]->Value;

// insert the verses into the database
$verse_id = 1;
$book_number = 1;
$data = '';
$book_data = '';
foreach ( $books as $bookname => $chapters )
{

	$chapter_number = 1;
	
	$book_data .= "( '{$book_number}', '{$bookname}' ),";
	
	// so long as we aren't approaching the maximum packet size, add more values
	if( strlen( $book_data ) >= ( $maxsize - 200 ) )
	{
		$book_data = substr( $book_data, 0, strlen( $book_data ) - 1 );
		$wpdb->query( "INSERT IGNORE INTO `{$table_biblebook}` ( `id`, `name` ) VALUES {$book_data}" );
		$book_data = '';
	}
	
	foreach ( $chapters as $verse )
	{
	
		$verse_of_chapter = 1;
	
		while ( $verse_of_chapter <= $verse )
		{
		
			$data .= "( $verse_id, $book_number, $chapter_number, $verse_of_chapter ),";
			
			if( strlen( $data ) >= ( $maxsize - 200 ) )
			{
				$data = substr( $data, 0, strlen( $data ) - 1 );
				$wpdb->query( "INSERT IGNORE INTO `{$table_thebible}` ( `id`, `book`, `chapter`, `verse` ) VALUES {$data}" );
				$data = '';
			}
			
			$verse_of_chapter++;
			$verse_id++;
		
		}
		
		$chapter_number++;
	
	}

	$book_number++;

}

//wrap up any remaining bits
if ( strlen( $data ) >= 4 )
{
	$data = substr( $data, 0, strlen( $data ) - 1 );
	$wpdb->query( "INSERT IGNORE INTO `{$table_thebible}` ( `id`, `book`, `chapter`, `verse` ) VALUES {$data}" );
}

// wrap up any remaining bits
if( strlen( $book_data ) >= 4 )
{
	$book_data = substr( $book_data, 0, strlen( $book_data ) - 1 );
	$wpdb->query( "INSERT IGNORE INTO `{$table_biblebook}` ( `id`, `name` ) VALUES {$book_data}" );
}

// ===== end of install =====
endif;