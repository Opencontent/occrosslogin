<?php
set_time_limit ( 0 );
#require 'autoload.php';

try
{
    $cli = eZCLI::instance();
}
catch (Exception $e)
{
	print_r($e,true);
}

$script = eZScript::instance( array( 'description' => ( "eZ Publish OpenContent CrossLogin Extension.\n\n"),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => false,
                                     'debug-message' => false) );
 
$script->startup();
$script->initialize();
 
OCToken::deleteExpiredTokens( $cli );

$cli->output( 'All done!' . "\n\n" );
$script->shutdown();
?>