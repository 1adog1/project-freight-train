<?php

    declare(strict_types = 1);

    /*

        Declare ESI Methods to be used by the app. These methods should be declared in the Ridley\Objects\ESI\Methods class, each accepting a single array as an argument.

        The $this->register method accepts the following arguments:

            [string] endpoint: The ESI endpoint name for which the method is being registered.
            [string] method: The name of the method to be called.
            [array] requiredArguments: The arguments required for method.

    */

    $this->register(
        endpoint: "/characters/affiliation/",
        method: "character_affiliations",
        requiredArguments: ["characters"]
    );

    $this->register(
        endpoint: "/characters/{character_id}/search/",
        method: "authenticated_search",
        requiredArguments: ["character_id", "categories", "search"]
    );

    $this->register(
        endpoint: "/universe/names/",
        method: "universe_names",
        requiredArguments: ["ids"]
    );
    
    $this->register(
        endpoint: "/route/{origin}/{destination}/",
        method: "route",
        requiredArguments: ["origin", "destination"]
    );

?>
