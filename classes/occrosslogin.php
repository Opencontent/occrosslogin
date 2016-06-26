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
            eZDebug::writeNotice('needRedirectionToLoginAccess',__METHOD__);
            $helper->redirectToLoginAccess();
        }

        if ($helper->needRedirectionToNotLoginAccess()) {
            eZDebug::writeNotice('needRedirectionToNotLoginAccess',__METHOD__);
            $helper->redirectToNotLoginAccess();
        }        
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
        
        if ( eZINI::instance()->variable( 'DebugSettings', 'DebugRedirection' ) !== 'disabled' ){
            $tpl = eZTemplate::factory();
            $tpl->setVariable( 'site', array() );
            $tpl->setVariable( 'warning_list', false );
            $tpl->setVariable( 'redirect_uri', $url );
            $templateResult = $tpl->fetch( 'design:redirect.tpl' );            
            
            eZDebug::addTimingPoint( "Script end" );
            
            eZDisplayResult( $templateResult );
            eZExecution::cleanExit();        
    
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
        $this->setToken();        
        $result = $this->isLoginAccessModule();
        
        return $this->isEnabled()
                && $result == false
                && $this->isLoginSiteAccess();        
    }
    
    public function redirectToNotLoginAccess()
    {
        if ( $this->http->hasSessionVariable('CrossRedirect') ){
            $params = $this->http->sessionVariable('CrossRedirect');
            $this->http->removeSessionVariable( 'CrossRedirect' );
            if ( isset( $params['token'] ) ){
                $path = $params['page'] . '?t=' .  $params['token'];
            }else{
                $path = $this->getCurrentUri()->originalURIString(true);
            }
            $this->redirect($path, array('host' => $params['host']) );
        }else{
            $checkUri = clone $this->getCurrentUri();            
            $saIni = eZSiteAccess::getIni($this->defaultSiteAccess);
            $args = array(
                'host' => $saIni->variable('SiteSettings', 'SiteURL')
            );
            $this->redirect($this->getCurrentUri()->originalURIString(true), $args);
        }
        eZExecution::cleanExit();
    }
    
    protected function setToken()
    {
        if ( eZUser::isCurrentUserRegistered()
             && $this->isEnabled()
             && $this->http->hasSessionVariable('CrossRedirect')
        ){            
            $params = $this->http->sessionVariable('CrossRedirect');
            if (isset($params['need_token'])){
                $session = session_id();
                $token = OCToken::generateToken( eZUser::currentUserID(), $session );
                $params['token'] = $token;
                unset($params['need_token']);
                $this->http->setSessionVariable( 'CrossRedirect', $params );
                eZDebug::writeNotice('setToken',__METHOD__);
            }            
        }
    }
    
    protected function setNeedToken()
    {
        if ( $this->isEnabled()
             && $this->http->hasSessionVariable('CrossRedirect')
        ){            
            $params = $this->http->sessionVariable('CrossRedirect');
            if (!isset($params['need_token'])){                
                $params['need_token'] = true;
                $this->http->setSessionVariable( 'CrossRedirect', $params );
                eZDebug::writeNotice('setNeedToken',__METHOD__);
            }            
        }
    }
    
    protected function setCrossRedirect()
    {
        if ( $this->isEnabled() && $this->isLoginSiteAccess() && !$this->http->hasSessionVariable('CrossRedirect') ){            
            $redirectSiteaccess = $this->http->getVariable( 'redirect', $this->defaultSiteAccess );
            $saIni = eZSiteAccess::getIni($redirectSiteaccess);
            $host = rtrim( $saIni->variable('SiteSettings', 'SiteURL'), '/' );
            $defaultPage = $saIni->variable( 'SiteSettings', 'DefaultPage' );
            $redirectPage = '/' . trim( $this->http->getVariable( 'url', $defaultPage ), '/' );            
            $this->http->setSessionVariable( 'CrossRedirect', array( 'host' => $host, 'page' => $redirectPage ) );
            eZDebug::writeNotice('setCrossRedirect',__METHOD__);
        } 
    }
    
    public function isLoginAccessModule()
    {
        $checkUri = clone $this->getCurrentUri();
        $checkUri->toBeginning();
        
        $moduleName = $checkUri->element();
        
        $module = eZModule::exists($moduleName);
                
        $checkUri->increase();
        $viewName = $checkUri->element();
        
        $checkUri->increase();
        $param = $checkUri->element();
        
        $redirectByModule = false;
        
        if ($module instanceof eZModule) {
            $redirectByModule = in_array($module->Name, $this->redirectModules);
                
            if ($moduleName == 'content'
                && $viewName == 'edit'
                && $param == eZUser::currentUserID()
                && in_array('user', $this->redirectModules) ){               
               $redirectByModule = true; 
            }
        }
        
        return $redirectByModule ? array('module'=> $module, 'current_view' => $viewName) : false;
    }
    
    public function needRedirectionToLoginAccessByModule()
    {                             
        $result = $this->isLoginAccessModule();
        if (is_array($result)) {
            $this->setCrossRedirect();
            
            if ( $result['current_view'] == 'login' ){
                $this->setNeedToken();
                eZUser::logoutCurrent();                
            }
            if ( $result['current_view'] == 'logout' ){
                eZUser::logoutCurrent();                
            }
        }        
        return is_array($result);
    }

    public function needRedirectionToLoginAccess()
    {        
        return $this->isEnabled()
                && $this->needRedirectionToLoginAccessByModule()
                && !$this->isLoginSiteAccess();
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
}