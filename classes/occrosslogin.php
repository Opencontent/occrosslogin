<?php

class OcCrossLogin
{
    private static $instance;

    /**
     * @var eZINI
     */
    protected $ini;

    protected $isEnabled;

    protected $loginSiteAccess;

    protected $redirectModules;

    protected function __construct()
    {
        $this->ini = eZINI::instance('crosslogin.ini');
        $this->isEnabled = false;
        if ( $this->ini->hasVariable('CrossLogin','EnableRedirection') ) {
            $this->isEnabled = $this->ini->variable('CrossLogin','EnableRedirection') == 'enabled';
        }
        $this->loginSiteAccess = $this->ini->variable('CrossLogin','LoginSiteAccess');
        $this->redirectModules = (array)$this->ini->variable('CrossLogin','RedirectModules');
    }

    public function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static public function inputListener(eZURI $uri)
    {
        $helper = self::instance();
        if ($helper->needRedirectionToLoginAccess($uri)) {
            $helper->redirectToLoginAccess();
        }
    }

    public function redirectToLoginAccess()
    {
        $saIni = eZSiteAccess::getIni( $this->loginSiteAccess );
        $args = array(
            'host' => $saIni->variable( 'SiteSettings', 'SiteURL' )
        );
        eZHTTPTool::redirect('user/login', $args);
        eZExecution::cleanExit();
    }

    public function needRedirectionToLoginAccess(eZURI $uri)
    {
        $siteaccess = eZSiteAccess::current();
        if ( $siteaccess && $this->isEnabled )
        {
            $saName = $siteaccess['name'];

            $checkUri = clone $uri;
            $moduleName = $checkUri->element();
            $module = eZModule::exists( $moduleName );
            if ( $module instanceof eZModule ){
                if ( in_array( $module->Name, $this->redirectModules ) && $saName !== $this->loginSiteAccess ){
                    return true;
                }
            }
        }
        return false;
    }
}