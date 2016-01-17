<?php

class OCTokenObject extends eZPersistentObject
{

    function __construct( $row )
    {
        parent::__construct( $row );
    }

    static function definition()
    {
        return array( 'fields' => array( 'user_id' => array( 'name' => 'UserID',
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => false ),
                                         'time' => array( 'name' => 'Time',
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => false ),                                         
                                         'session_id' => array( 'name' => 'SessionId',
                                                             'datatype' => 'string',
                                                             'default' => ''),
                                         'token' => array( 'name' => 'Token',
                                                             'datatype' => 'string',
                                                             'default' => '',                                                             
                                                             'required' => false )),                     
                      'function_attributes' => array(),
                      'keys' => array( 'token' ),
                      'class_name' => 'OCTokenObject',
                      'sort' => array( 'time' => 'asc' ),
                      'name' => 'ezoctoken' );
    }

	static function fetch($id)
	{
		return eZPersistentObject::fetchObject( OCTokenObject::definition(), null, array('id' => $id) );
	}
	
	static function fetchByToken($token)
	{
		return eZPersistentObject::fetchObject( OCTokenObject::definition(), null, array('token' => $token) );
	}

	static function fetchByTime( $operator = '<=', $time = false )
	{
		if ( !$time ) 
			$time = time();
		return eZPersistentObject::fetchObjectList( OCTokenObject::definition(), null, array('time' => array( $operator, $time ) ) );
	}
	
	static function fetchAll()
	{
		return eZPersistentObject::fetchObjectList( OCTokenObject::definition(), null, null );
	}
}

?>
