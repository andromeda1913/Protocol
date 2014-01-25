<?php 
  
session_start() ;
	
 
	include_once './defines.php';
    require_once( $MM_GLOBALS['home'] . "include/utilities.inc" );

     $today = date( "M-d-Y H:i:s" );
	 $dreamInfo = "Dreaming for database {$MM_GLOBALS['dbName']} on server {$MM_GLOBALS['dbHost']}";
	 $mm = new mindmeld();
	 $event = new mmEvent;
	 $event->dream( 'start dream', $dreamInfo  );
	 $mm = $mm->pluginMgr->executePlugins( 'dream', $mm );
	 $event->dream( 'stop dream', $dreamInfo );
 