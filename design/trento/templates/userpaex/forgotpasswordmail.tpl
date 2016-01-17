{def $site_url=ezini("SiteSettings","SiteURL")}
{set-block scope=root variable=subject}{$site_url} Password dimenticata{/set-block}
Le informazioni sul tuo account:
Email: {$user.email}

Clicca sul seguente indirizzo per generare una nuova password
{concat("userpaex/forgotpassword/", $hash_key, '/')|ezurl(no)}

Il link e' valido fino al {$hash_key_lifetime}.
{undef $site_url}
