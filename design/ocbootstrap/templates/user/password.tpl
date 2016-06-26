<div class="row">
<div class="col-md-8 col-md-offset-2">

    <form action={concat($module.functions.password.uri,"/",$userID)|ezurl} method="post" name="Password">

    <div class="attribute-header">
        <h1 class="long">{"Change password for user"|i18n("design/ocbootstrap/user/password")} {$userAccount.login}</h1>
    </div>

    {if $message}
    {if or( $oldPasswordNotValid, $newPasswordNotMatch, $newPasswordTooShort )}
        {if $oldPasswordNotValid}
            <div class="warning">
                <h2>{'Please retype your old password.'|i18n('design/ocbootstrap/user/password')}</h2>
            </div>
        {/if}
        {if $newPasswordNotMatch}
            <div class="warning">
                <h2>{"Password didn't match, please retype your new password."|i18n('design/ocbootstrap/user/password')}</h2>
            </div>
        {/if}
        {if $newPasswordTooShort}
            <div class="warning">
                <h2>{"The new password must be at least %1 characters long, please retype your new password."|i18n( 'design/ocbootstrap/user/password','',array( ezini('UserSettings','MinPasswordLength') ) )}</h2>
            </div>
        {/if}

    {else}
        <div class="feedback">
            <h2>{'Password successfully updated.'|i18n('design/ocbootstrap/user/password')}</h2>
        </div>
    {/if}

    {/if}

        {if $oldPasswordNotValid}*{/if}
        <label>{"Old password"|i18n("design/ocbootstrap/user/password")}</label><div class="labelbreak"></div>
        <input class="form-control" type="password" name="oldPassword" size="11" value="{$oldPassword|wash}" />

            {if $newPasswordNotMatch}*{/if}
            <label>{"New password"|i18n("design/ocbootstrap/user/password")}</label><div class="labelbreak"></div>
            <input class="form-control" autocomplete="off" type="password" name="newPassword" size="11" value="{$newPassword|wash}" />
            {if $newPasswordNotMatch}*{/if}
            <label>{"Retype password"|i18n("design/ocbootstrap/user/password")}</label><div class="labelbreak"></div>
            <input class="form-control" autocomplete="off" type="password" name="confirmPassword" size="11" value="{$confirmPassword|wash}" />

        <input class="defaultbutton" type="submit" name="OKButton" value="{'OK'|i18n('design/ocbootstrap/user/password')}" />
        <input class="button" type="submit" name="CancelButton" value="{'Torna al sito'|i18n('design/ocbootstrap/user/password')}" />

    </form>
</div>
</div>

