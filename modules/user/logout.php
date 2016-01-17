<?php
$http = eZHTTPTool::instance();

$user = eZUser::instance();

//@luca modifiche al modulo originale
$redirect = false;
$queryString = eZSys::serverVariable( 'QUERY_STRING' );
parse_str( $queryString );
if( $redirect )
{
    $http->setSessionVariable( 'CrossRedirect', $redirect );
}
else
{
    $http->setSessionVariable( 'CrossRedirect', $_SERVER['HTTP_HOST'] );  
}
//@luca fine modifiche al modulo originale

// Remove all temporary drafts
eZContentObject::cleanupAllInternalDrafts( $user->attribute( 'contentobject_id' ) );

$user->logoutCurrent();

$http->setSessionVariable( 'force_logout', 1 );

$ini = eZINI::instance();
if ( $ini->variable( 'UserSettings', 'RedirectOnLogoutWithLastAccessURI' ) == 'enabled' && $http->hasSessionVariable( 'LastAccessesURI'))
{
    $redirectURL = $http->sessionVariable( "LastAccessesURI" );
}
else
{
    $redirectURL = $ini->variable( 'UserSettings', 'LogoutRedirect' );
}

//$cIni = eZINI::instance('crosslogin.ini');
//$cIni->variable( 'CrossLogin', 'LoginHostName' );

if ( $http->hasSessionVariable( 'CrossRedirect' )
    && ( eZSys::hostname() !== $http->sessionVariable( 'CrossRedirect' ) ) )
{
    $redirectURL = 'http://' . $http->sessionVariable( 'CrossRedirect' );
    $redirectURL = str_replace( '/user/logout', '', $redirectURL );
    $http->removeSessionVariable( 'CrossRedirect' );
    $redirectURL = $redirectURL . '/user/do_logout';
}

return $Module->redirectTo( $redirectURL );

?>
