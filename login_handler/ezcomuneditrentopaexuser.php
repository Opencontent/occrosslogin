<?php

class eZComuneDiTrentoPaExUser extends eZUser
{
    /**
     * Holds the URL to the password change form
     *
     * @var string
     */
    protected static $changePasswordFormURL = "/userpaex/password/";

    /**
     * Pure wrapper for eZUser::__construct( $row ). Used to mute errors due to the absence of $row as parameter,
     * when called from eZUserLoginHandler::instance(), while according to the signature of eZUser::eZUser(), $row
     * is not optional.
     * This should be removed as soon as eZUser::eZUser() is fixed.
     *
     * @param string $row eZPersistenObject-compliant data row.
     * @see eZUser::eZUser()
     *
     */
    public function __construct( $row = null )
    {
        @parent::eZUser( $row );
    }

    /**
     * Logs in the user if applied login and password is valid.
     *
     * @param string $login
     * @param string $password
     * @param bool $authenticationMatch
     * @return mixed eZUser or false
     */
    public static function loginUser( $login, $password, $authenticationMatch = false )
    {
        $user = self::_loginUser( $login, $password, $authenticationMatch );
        if ( is_object( $user ) )
        {
            $userID = $user->attribute( 'contentobject_id' );
            
            $paex = false;
            if ( class_exists( 'eZPaEx' ) )
                $paex = eZPaEx::getPaEx( $userID );                

            if ( $paex instanceof eZPaEx && $paex->isExpired() )
            {
                self::passwordHasExpired( $user );
                return false;
            }
            else
            {
                self::loginSucceeded( $user );
                return $user;
            }
        }
        else
        {
            self::loginFailed( $user, $login );
            return false;
        }

        return $user;
    }

    /**
     * Writes audit information and redirects the user to the password change form.
     *
     * @param eZUser $user
     */
    protected static function passwordHasExpired( $user )
    {
        $userID = $user->attribute( 'contentobject_id' );

        // Password expired
        eZDebugSetting::writeDebug( 'kernel-user', $user, 'user password expired' );

        // Failed login attempts should be logged
        $userIDAudit = isset( $userID ) ? $userID : 'null';
        $loginEscaped = eZDB::instance()->escapeString( $user->attribute( 'login' ) );
        eZAudit::writeAudit( 'user-failed-login', array( 'User id' => $userIDAudit,
                                                         'User login' => $loginEscaped,
                                                         'Comment' => 'Failed login attempt: Password Expired. eZPaExUser::loginUser()' ) );

         // Redirect user to password change form
         self::redirectToChangePasswordForm( $userID );
    }

    /**
     * Performs a redirect to the password change form
     *
     * @param int $userID
     */
    protected static function redirectToChangePasswordForm( $userID )
    {
        $http = eZHTTPTool::instance();
        $url = self::$changePasswordFormURL . $userID;
        //@luca
        //eZURI::transformURI( $url );
        if ( $http->hasSessionVariable( 'CrossRedirect' ) )
        {
            $session = session_id();
            $token = OCToken::generateToken( $userID, $session );
            $redirectionURI = $http->sessionVariable( 'CrossRedirect' );
            if ( substr( $redirectionURI, -1, 1 ) == '/' )
            {
                $redirectionURI = $redirectionURI . '?t=' . $token;    
            }
            else
            {
                $redirectionURI = $redirectionURI . '/?t=' . $token;
            }
            $http->setSessionVariable( 'RedirectAfterPasswordChange', $redirectionURI );
        }
        $http->setSessionVariable( 'CrossRedirect', $url );
        //@luca $http->redirect( $url );
    }
}

?>