<?php

class OCLDAPTools
{
    static public function authenticate_ldap_user( eZUser $user, $password )
    {
        if ( !$user instanceof eZUser )
        {            
            return false;
        }
        
        $ini = eZINI::instance();
        $LDAPIni = eZINI::instance( 'ldap.ini' );
        
        $login = $user->attribute( 'login' );
        
        if ( $LDAPIni->variable( 'LDAPSettings', 'LDAPEnabled' ) == "true" )
        {            
            // read LDAP ini settings
            // and then try to bind to the ldap server

            $LDAPVersion    = $LDAPIni->variable( 'LDAPSettings', 'LDAPVersion' );
            $LDAPServer     = $LDAPIni->variable( 'LDAPSettings', 'LDAPServer' );
            $LDAPPort       = $LDAPIni->variable( 'LDAPSettings', 'LDAPPort' );
            $LDAPBaseDN     = $LDAPIni->variable( 'LDAPSettings', 'LDAPBaseDn' );
            $LDAPBindUser   = $LDAPIni->variable( 'LDAPSettings', 'LDAPBindUser' );
            $ActiveDirectoryDomain   = $LDAPIni->variable( 'LDAPSettings', 'ActiveDirectoryDomain' );
            $LDAPBindPassword       = $LDAPIni->variable( 'LDAPSettings', 'LDAPBindPassword' );
            $LDAPSearchScope        = $LDAPIni->variable( 'LDAPSettings', 'LDAPSearchScope' );

            $LDAPLoginAttribute     = $LDAPIni->variable( 'LDAPSettings', 'LDAPLoginAttribute' );
            $LDAPFirstNameAttribute = $LDAPIni->variable( 'LDAPSettings', 'LDAPFirstNameAttribute' );
            $LDAPLastNameAttribute  = $LDAPIni->variable( 'LDAPSettings', 'LDAPLastNameAttribute' );
            $LDAPEmailAttribute     = $LDAPIni->variable( 'LDAPSettings', 'LDAPEmailAttribute' );

            $LDAPUserGroupAttributeType = $LDAPIni->variable( 'LDAPSettings', 'LDAPUserGroupAttributeType' );
            $LDAPUserGroupAttribute     = $LDAPIni->variable( 'LDAPSettings', 'LDAPUserGroupAttribute' );

            if ( $LDAPIni->hasVariable( 'LDAPSettings', 'Utf8Encoding' ) )
            {
                $Utf8Encoding = $LDAPIni->variable( 'LDAPSettings', 'Utf8Encoding' );
                if ( $Utf8Encoding == "true" )
                    $isUtf8Encoding = true;
                else
                    $isUtf8Encoding = false;
            }
            else
            {
                $isUtf8Encoding = false;
            }

            if ( $LDAPIni->hasVariable( 'LDAPSettings', 'LDAPSearchFilters' ) )
            {
                $LDAPFilters = $LDAPIni->variable( 'LDAPSettings', 'LDAPSearchFilters' );
            }
            if ( $LDAPIni->hasVariable( 'LDAPSettings', 'LDAPUserGroupType' ) and  $LDAPIni->hasVariable( 'LDAPSettings', 'LDAPUserGroup' ) )
            {
                $LDAPUserGroupType = $LDAPIni->variable( 'LDAPSettings', 'LDAPUserGroupType' );
                $LDAPUserGroup = $LDAPIni->variable( 'LDAPSettings', 'LDAPUserGroup' );
            }

            $LDAPFilter = "( &";
            if ( count( $LDAPFilters ) > 0 )
            {
                foreach ( array_keys( $LDAPFilters ) as $key )
                {
                    $LDAPFilter .= "(" . $LDAPFilters[$key] . ")";
                }
            }
            $LDAPEqualSign = trim($LDAPIni->variable( 'LDAPSettings', "LDAPEqualSign" ) );
            $LDAPBaseDN = str_replace( $LDAPEqualSign, "=", $LDAPBaseDN );
            $LDAPFilter = str_replace( $LDAPEqualSign, "=", $LDAPFilter );

            $ds = ldap_connect( $LDAPServer, $LDAPPort );

            if ( $ds )
            {
                ldap_set_option( $ds, LDAP_OPT_PROTOCOL_VERSION, $LDAPVersion );
                if ( $LDAPBindUser == '' )
                {
                    $r = ldap_bind( $ds );
                }
                else
                {
                    $r = ldap_bind( $ds, $LDAPBindUser, $LDAPBindPassword );
                }

                if ( !$r )
                {
                    return false;
                }
                
                if ( !@ldap_bind( $ds, $login.$ActiveDirectoryDomain, $password ) )
                {
                    return false;   
                }
                
                return true;
              
            }
        }
    }
}

?>