<?php
require_once('autoload.php');

class OCToken
{
    private static $instance;
    
    /**
     * Constructor
     * 
     */
    function __construct()
    {
          	
    }
    
	public static function getInstance()
    {
		if ( self::$instance == null )
        {
			self::$instance = new OCToken(); 
		}
        return self::$instance;		
	}
	
	public static function generateToken( $userID, $sessionID )
	{
		$db = eZDB::instance();
        $db->begin();
		$token = md5( $userID . $sessionID );
		$newOCToken = new OCTokenObject(array(
			'user_id' => $userID,
			'time' => time(),
			'session_id' => $sessionID,
			'token' => $token
		));
		$newOCToken->store();
		$db->commit();
		return $token;
	}
	
	public static function checkToken( $token )
	{
		
		$OCToken = OCTokenObject::fetchByToken( $token );
		if ( $OCToken !== false )
		{
			$userID = $OCToken->attribute('user_id');
			$db = eZDB::instance();
        	$db->begin();
        	$OCToken->remove();
			$db->commit();
			return $userID;
		}
		return false;
	}
    
	public static function deleteExpiredTokens( $cli = false )
	{
		$db = eZDB::instance();
        $db->begin();
		$OCTokenIni = eZINI::instance( 'crosslogin.ini' );
		$interval = $OCTokenIni->hasvariable( 'TokenSettings', 'ExpiryInterval' ) ? $OCTokenIni->variable( 'TokenSettings', 'ExpiryInterval' ) : 0;
		$OCTokens = OCTokenObject::fetchAll();
		foreach ($OCTokens as $OCToken) 
		{
			$now = time();
			$tokenInterval = $OCToken->attribute( 'time' ) + $interval;
			if ( $now >= $tokenInterval )
			{
				if ( $cli )
					$cli->output( 'Delete token for user ' . $OCToken->attribute( 'user_id' ) . "\n" );
				$OCToken->remove();
			}
		}
		$db->commit();
	}

}
