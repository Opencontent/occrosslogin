<?php

class eZCrossloginSSOHandler
{
    function eZCrossloginSSOHandler()
    {
    }

    /*!
      Return the eZUser object to log in or false if no user should be
      logged in.
    */
    function handleSSOLogin()
    {
        $helper = OcCrossLogin::instance();
        $http = eZHTTPTool::instance();
        $token = false;
        
        if ( $http->hasGetVariable( 't' ) )
        {
            $token = $http->getVariable( 't' );
        }
        
        if ( !$token )
        {
            $t = false;
            parse_str( eZSys::serverVariable( 'QUERY_STRING' ) );
            if ( $t )
                $token = $t;
        }
        
        if ( $token )
        {
            $OCTokenObject = OCTokenObject::fetchByToken( $token );
            
            if ( !$OCTokenObject )
            {
                eZDebug::writeWarning( 'Token not found', __METHOD__ );
                return false;
            }
            
            $currentUser = eZUser::fetch( $OCTokenObject->attribute( 'user_id' ) );
            
            $db = eZDB::instance();
            $db->begin();
            $OCTokenObject->remove();
            $db->commit();
            
            if ( !$currentUser )
            {
                eZDebug::writeWarning( 'User with token ' . $token . ' not found', __METHOD__ );
                return false;
            }
            if ( $http->hasSessionVariable( 'CrossRedirect' ) && !$helper->isLoginSiteAccess())
                $http->removeSessionVariable( 'CrossRedirect' );
            return $currentUser;
        }
        eZDebug::writeNotice( 'Token request not found', __METHOD__ );
        return false;
    }
}
?>
