<?php

    $configVariables = [];

    if (file_exists(__DIR__ . "/config.ini")) {

        $configData = parse_ini_file(__DIR__ . "/config.ini");

        //EVE AUTHENTICATION CONFIGURATION
        $configVariables["Client ID"] = $configData["ClientID"];
        $configVariables["Client Secret"] = $configData["ClientSecret"];
        $configVariables["Client Scopes"] = $configData["ClientScopes"];
        $configVariables["Default Scopes"] = $configData["DefaultScopes"];
        $configVariables["Client Redirect"] = $configData["ClientRedirect"];
        $configVariables["Auth Type"] = $configData["AuthType"];
        $configVariables["Super Admins"] = explode(",", str_replace(" ", "", $configData["SuperAdmins"]));

        //NEUCORE AUTHENTICATION CONFIGURATION
        $configVariables["NeuCore ID"] = $configData["AppID"];
        $configVariables["NeuCore Secret"] = $configData["AppSecret"];
        $configVariables["NeuCore URL"] = $configData["AppURL"];

        //DATABASE SERVER CONFIGURATION
        $configVariables["Database Server"] = $configData["DatabaseServer"] . ":" . $configData["DatabasePort"];
        $configVariables["Database Username"] = $configData["DatabaseUsername"];
        $configVariables["Database Password"] = $configData["DatabasePassword"];

        //DATABASE NAME CONFIGURATION
        $configVariables["Database Name"] = $configData["DatabaseName"];

        //SITE CONFIGURATION
        $configVariables["Service Name"] = $configData["ServiceName"];
        $configVariables["Auth Cookie Name"] = $configData["AuthCookieName"];
        $configVariables["Session Time"] = $configData["SessionTime"];
        $configVariables["Auth Cache Time"] = $configData["AuthCacheTime"];
        $configVariables["Store Visitor IPs"] = boolval($configData["StoreVisitorIPs"]);

    }
    else {

        //$_ENV doesn't seem to always work, making our own array instead.
        $ENVS = getenv();

        //EVE AUTHENTICATION CONFIGURATION
        $configVariables["Client ID"] = $ENVS["ENV_FREIGHT_EVE_CLIENT_ID"];
        $configVariables["Client Secret"] = $ENVS["ENV_FREIGHT_EVE_CLIENT_SECRET"];
        $configVariables["Client Scopes"] = $ENVS["ENV_FREIGHT_EVE_CLIENT_SCOPES"] ?? "esi-search.search_structures.v1";
        $configVariables["Default Scopes"] = $ENVS["ENV_FREIGHT_EVE_DEFAULT_SCOPES"] ?? "esi-search.search_structures.v1";
        $configVariables["Client Redirect"] = $ENVS["ENV_FREIGHT_EVE_CLIENT_REDIRECT"];
        $configVariables["Auth Type"] = $ENVS["ENV_FREIGHT_EVE_AUTH_TYPE"] ?? "Neucore";
        $configVariables["Super Admins"] = explode(",", str_replace(" ", "", $ENVS["ENV_FREIGHT_EVE_SUPER_ADMINS"]));

        //NEUCORE AUTHENTICATION CONFIGURATION
        $configVariables["NeuCore ID"] = $ENVS["ENV_FREIGHT_NEUCORE_APP_ID"] ?? NULL;
        $configVariables["NeuCore Secret"] = $ENVS["ENV_FREIGHT_NEUCORE_APP_SECRET"] ?? NULL;
        $configVariables["NeuCore URL"] = $ENVS["ENV_FREIGHT_NEUCORE_APP_URL"] ?? NULL;

        //DATABASE SERVER CONFIGURATION
        $configVariables["Database Server"] = $ENVS["ENV_FREIGHT_DATABASE_SERVER"] . ":" . $ENVS["ENV_FREIGHT_DATABASE_PORT"];
        $configVariables["Database Username"] = $ENVS["ENV_FREIGHT_DATABASE_USERNAME"];
        $configVariables["Database Password"] = $ENVS["ENV_FREIGHT_DATABASE_PASSWORD"];

        //DATABASE NAME CONFIGURATION
        $configVariables["Database Name"] = $ENVS["ENV_FREIGHT_DATABASE_NAME"];

        //SITE CONFIGURATION
        $configVariables["Service Name"] = $ENVS["ENV_FREIGHT_WEBSITE_SERVICE_NAME"];
        $configVariables["Auth Cookie Name"] = $ENVS["ENV_FREIGHT_WEBSITE_AUTH_COOKIE"] ?? "IntelAuthID";
        $configVariables["Session Time"] = (int)($ENVS["ENV_FREIGHT_WEBSITE_SESSION_TIME"] ?? 43200);
        $configVariables["Auth Cache Time"] = (int)($ENVS["ENV_FREIGHT_WEBSITE_AUTH_CACHE_TIME"] ?? 600);
        $configVariables["Store Visitor IPs"] = boolval(($ENVS["ENV_FREIGHT_WEBSITE_STORE_IPS"] ?? 0));

    }

?>
