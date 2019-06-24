<?php

class OcCrossLoginOperators
{
    function operatorList()
    {
        return array('can_login');
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array();
    }

    function modify(
        &$tpl,
        &$operatorName,
        &$operatorParameters,
        &$rootNamespace,
        &$currentNamespace,
        &$operatorValue,
        &$namedParameters
    ) {

        switch ($operatorName) {

            case 'can_login': {
                $operatorValue = true;
                if (OcCrossLogin::instance()->isEnabled())
                    $operatorValue = OcCrossLogin::instance()->isLoginSiteAccess();
            } break;
        }
    }

}

?>
