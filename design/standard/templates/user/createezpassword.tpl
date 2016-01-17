<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">



{if $message}
{if or( $oldPasswordNotValid, $newPasswordNotMatch, $newPasswordTooShort )}
    {if $oldPasswordNotValid}
        <div class="warning">
            <h2>La password che usi per accedere dall'ufficio non risulta corretta.</h2>
        </div>
    {/if}
    {if $newPasswordNotMatch}
        <div class="warning">
            <h2>{"Password didn't match, please retype your new password."|i18n('design/ezwebin/user/password')}</h2>
        </div>
    {/if}
    {if $newPasswordTooShort}
        <div class="warning">
            <h2>{"The new password must be at least %1 characters long, please retype your new password."|i18n( 'design/ezwebin/user/password','',array( ezini('UserSettings','MinPasswordLength') ) )}</h2>
        </div>
    {/if}

{else}
    <div class="feedback">
        <h2>La password per accedere da casa &egrave; stata creata con successo.</h2>
        <p>Con la password che hai appena creato, puoi accedere al sistema da casa o da fuori ufficio.</p>
    </div>
{/if}

{/if}


{if $show_form}

<div class="maincontentheader">
<h1>Crea una password per accedere al sistema da casa.</h1>
</div>

<form action={concat($module.functions.createezpassword.uri,"/",$userID)|ezurl} method="post" name="Password">

<div class="block">

<div class="block">

<label>Digita la password che usi per accedere al sistema dall'ufficio{if $oldPasswordNotValid}*{/if}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="oldPassword" size="11" value="{$oldPassword|wash}" />
</div>

<label>Crea una nuova password per accedere al sistema da casa {if $newPasswordNotMatch}*{/if}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="newPassword" size="11" value="{$newPassword}" />

<label>Ridigita la nuova password {if $newPasswordNotMatch}*{/if}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="confirmPassword" size="11" value="{$confirmPassword}" />

</div>

<div class="buttonblock">
<input class="defaultbutton" type="submit" name="OKButton" value="{'OK'|i18n('design/standard/user')}" />
<input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n('design/standard/user')}" />
</div>

</form>

{/if}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
