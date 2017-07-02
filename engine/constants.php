<?php
define('G_ROOT_PATH', dirname(__DIR__)); //defines root path , where index.php is located
define('G_SMARTY_DIR', dirname(__DIR__).'/modules/drawer/'); //smarty module location folder (Smarty requirement)
define('G_INCOMING_SERVER_FOLDER_PATH_WITH_DATA_FILES', dirname(__DIR__).'/datafiles/incoming/'); //server folder , which contains files to process
define('G_PROCESSING_SERVER_FOLDER_PATH_WITH_DATA_FILES', dirname(__DIR__).'/datafiles/processing/'); //server folder , which contains files under processing
define('G_COMPLETED_SERVER_FOLDER_PATH_WITH_DATA_FILES', dirname(__DIR__).'/datafiles/completed/'); //server folder , which contains files after processing