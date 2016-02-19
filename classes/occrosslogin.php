<?php

class OcCrossLogin
{
    private static $instance;

    /**
     * @var eZINI
     */
    protected $ini;

    protected $isEnabled;

    public $loginSiteAccess;
    
    public $defaultSiteAccess;
    
    public $currentSiteAccess;

    protected $redirectModules;
    
    /**
     * @var eZURI
     */
    protected $currentUri;
    
    /**
     * @var eZHTTPTool
     */ 
    public $http;

    protected function __construct()
    {
        $this->ini = eZINI::instance('crosslogin.ini');
        $this->isEnabled = false;
        if ($this->ini->hasVariable('CrossLogin', 'EnableRedirection')) {
            $this->isEnabled = $this->ini->variable('CrossLogin', 'EnableRedirection') == 'enabled';
        }
        $this->loginSiteAccess = $this->ini->variable('CrossLogin', 'LoginSiteAccess');
        $this->defaultSiteAccess = $this->ini->variable('CrossLogin', 'DefaultSiteAccess');
        $this->redirectModules = (array)$this->ini->variable('CrossLogin', 'RedirectModules');
        $siteaccess = eZSiteAccess::current();
        if ($siteaccess) {
            $this->currentSiteAccess = $siteaccess['name'];
        }
        $this->http = eZHTTPTool::instance();
    }
    
    public function setCurrentUri( eZURI $uri ){
        $this->currentUri = $uri;
    }
    
    public function getCurrentUri(){
        if ( $this->currentUri === null )
            $this->currentUri = new eZURI( '/' );
        return $this->currentUri;
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    static public function inputListener(eZURI $uri)
    {        
        $helper = self::instance();
        $helper->setCurrentUri( $uri );        
        if ($helper->needRedirectionToLoginAccess()) {
            $helper->redirectToLoginAccess();
        }

        if ($helper->needRedirectionToNotLoginAccess()) {
            $helper->redirectToNotLoginAccess();
        }        
    }

    public function redirectToLoginAccess()
    {
        $saIni = eZSiteAccess::getIni($this->loginSiteAccess);
        $args = array(
            'host' => $saIni->variable('SiteSettings', 'SiteURL')
        );
        $params = array(
            'redirect' => $this->currentSiteAccess,
            'url' => $this->http->getVariable( 'url', '/' )
        );
        $query = http_build_query( $params );        
        $this->redirect($this->getCurrentUri()->originalURIString(true).'?'.$query, $args);
        eZExecution::cleanExit();
    }

    public function redirectToNotLoginAccess()
    {
        if ( $this->http->hasSessionVariable('CrossRedirect') ){
            $params = $this->http->sessionVariable('CrossRedirect');
            $this->http->removeSessionVariable( 'CrossRedirect' );
            $this->redirect($params['page'], array('host' => $params['host']) );
        }else{
            $saIni = eZSiteAccess::getIni($this->defaultSiteAccess);
            $args = array(
                'host' => $saIni->variable('SiteSettings', 'SiteURL')
            );
            $this->redirect($this->getCurrentUri()->originalURIString(true), $args);
        }
        eZExecution::cleanExit();
    }
    
    protected function redirect( $path, $parameters = array(), $status = false, $encodeURL = true, $returnRedirectObject = false )
    {
        $url = eZHTTPTool::createRedirectUrl( $path, $parameters );
        if ( strlen( $status ) > 0 )
        {
            header( $_SERVER['SERVER_PROTOCOL'] .  " " . $status );
            eZHTTPTool::headerVariable( "Status", $status );
        }

        if ( $encodeURL )
        {
            $url = eZURI::encodeURL( $url );
        }

        eZHTTPTool::headerVariable( 'Location', $url );
        /* Fix for redirecting using workflows and apache 2 */
        $escapedUrl = htmlspecialchars( $url );
        $content = <<<EOT
<HTML><HEAD>
<META HTTP-EQUIV="Refresh" Content="0;URL=$escapedUrl">
<META HTTP-EQUIV="Location" Content="$escapedUrl">
</HEAD><BODY></BODY></HTML>
EOT;

        if ( $returnRedirectObject )
        {
            return new ezpKernelRedirect( $url, $status ?: null, $content );
        }

        echo $content;
    }

    public function isLoginSiteAccess()
    {
        return $this->currentSiteAccess == $this->loginSiteAccess;
    }

    public function isEnabled(){
        return $this->isEnabled;
    }

    public function needRedirectionToNotLoginAccess()
    {
        if ( eZUser::isCurrentUserRegistered()
             && $this->isEnabled()
             && $this->http->hasSessionVariable('CrossRedirect')
        ){
            $session = session_id();
            $token = OCToken::generateToken( eZUser::currentUserID(), $session );
            $params = $this->http->sessionVariable('CrossRedirect');
            $params['page'] = $params['page'] . '?t=' . $token;
            $this->http->setSessionVariable( 'CrossRedirect', $params );            
            return true;
        }else{
        
            $checkUri = clone $this->getCurrentUri();
            $moduleName = $checkUri->element();
            $module = eZModule::exists($moduleName);
            
            $redirectByModule = false;
            if ($module instanceof eZModule) {
                $redirectByModule = in_array($module->Name, $this->redirectModules);
            }
            return $this->isEnabled()
                    && !$redirectByModule
                    && $this->isLoginSiteAccess();
        }
    }

    public function needRedirectionToLoginAccess()
    {
        $checkUri = clone $this->getCurrentUri();
        $moduleName = $checkUri->element();
        $module = eZModule::exists($moduleName);        
        $redirectByModule = false;
        if ($module instanceof eZModule) {
            $redirectByModule = in_array($module->Name, $this->redirectModules);
            $checkUri->increase();
            $viewName = $checkUri->element();
            if ( $viewName == 'login' ){
                eZUser::logoutCurrent();
                if ( $this->isEnabled() && $this->isLoginSiteAccess() && !$this->http->hasSessionVariable('CrossRedirect') ){            
                    $redirectSiteaccess = $this->http->getVariable( 'redirect', $this->defaultSiteAccess );
                    $saIni = eZSiteAccess::getIni($redirectSiteaccess);
                    $host = rtrim( $saIni->variable('SiteSettings', 'SiteURL'), '/' );
                    $defaultPage = $saIni->variable( 'SiteSettings', 'DefaultPage' );
                    $redirectPage = '/' . trim( $this->http->getVariable( 'url', $defaultPage ), '/' );            
                    $this->http->setSessionVariable( 'CrossRedirect', array( 'host' => $host, 'page' => $redirectPage ) );
                }   
            }else{
                $this->http->removeSessionVariable( 'CrossRedirect' );
                return false;
            }
        }        
        return $this->isEnabled()
                && $redirectByModule
                && !$this->isLoginSiteAccess();
    }
}