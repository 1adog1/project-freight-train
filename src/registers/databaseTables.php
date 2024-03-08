<?php

    declare(strict_types = 1);
    
    /*
        Define tables to add to the database here.
        
        The $siteDatabase->register method accepts the following arguments:
        
            A single $tableName string. 
            A variable amount of $tableColumns arrays.
            
        Each $tableColumns array can have the following keys:
        
            [REQUIRED] "Name" - The name of the column.
            [REQUIRED] "Type" - The SQL type of the column. 
            [OPTIONAL] "Special" - Any special modifiers for the column. 
            
        EXAMPLE:
        
            $siteDatabase->register(
                "table_name",
                ["Name" => "special_column", "Type" => "BIGINT", "Special" => "primary key AUTO_INCREMENT"],
                ["Name" => "column_two", "Type" => "TEXT"]
            );
            
    */

    $siteDatabase->register(
        "evesystems",
        ["Name" => "id", "Type" => "BIGINT", "Special" => "PRIMARY KEY"],
        ["Name" => "name", "Type" => "VARCHAR(255)"], 
        ["Name" => "regionid", "Type" => "BIGINT"], 
        ["Name" => "regionname", "Type" => "VARCHAR(255)"], 
        ["Name" => "class", "Type" => "VARCHAR(63)"], 
        ["Name" => "security", "Type" => "DOUBLE"], 
        ["Name" => "x", "Type" => "NUMERIC(20)"], 
        ["Name" => "y", "Type" => "NUMERIC(20)"], 
        ["Name" => "z", "Type" => "NUMERIC(20)"],
        ["Name" => "", "Type" => "", "Special" => "INDEX (name)"],
        ["Name" => "", "Type" => "", "Special" => "INDEX (regionid)"],
        ["Name" => "", "Type" => "", "Special" => "INDEX (regionname)"],
        ["Name" => "", "Type" => "", "Special" => "INDEX (class)"]
    );

    $siteDatabase->register(
        "options",
        ["Name" => "iteration", "Type" => "BIGINT", "Special" => "primary key AUTO_INCREMENT"],
        ["Name" => "contractcorporation", "Type" => "TEXT"], 
        ["Name" => "onlyapprovedroutes", "Type" => "TINYINT"], 
        ["Name" => "allowhighsectohighsec", "Type" => "TINYINT"], 
        ["Name" => "allowlowsec", "Type" => "TINYINT"], 
        ["Name" => "allownullsec", "Type" => "TINYINT"], 
        ["Name" => "allowwormholes", "Type" => "TINYINT"], 
        ["Name" => "allowpochven", "Type" => "TINYINT"], 
        ["Name" => "allowrush", "Type" => "TINYINT"], 
        ["Name" => "rushmultiplier", "Type" => "NUMERIC(8,4)"], 
        ["Name" => "nonstandardmultiplier", "Type" => "NUMERIC(8,4)"], 
        ["Name" => "maxvolume", "Type" => "BIGINT"], 
        ["Name" => "maxcollateral", "Type" => "BIGINT"], 
        ["Name" => "blockaderunnercutoff", "Type" => "BIGINT"], 
        ["Name" => "maxthresholdprice", "Type" => "BIGINT"], 
        ["Name" => "highsectohighsecmaxvolume", "Type" => "BIGINT"], 
        ["Name" => "gateprice", "Type" => "BIGINT"], 
        ["Name" => "maxwormholevolume", "Type" => "BIGINT"], 
        ["Name" => "wormholeprice", "Type" => "BIGINT"], 
        ["Name" => "maxpochvenvolume", "Type" => "BIGINT"], 
        ["Name" => "pochvenprice", "Type" => "BIGINT"], 
        ["Name" => "collateralpremium", "Type" => "NUMERIC(8,4)"]
    );

    $siteDatabase->register(
        "tiers",
        ["Name" => "threshold", "Type" => "NUMERIC(8,4)", "Special" => "primary key"],
        ["Name" => "price", "Type" => "BIGINT"]
    );

    $siteDatabase->register(
        "routes",
        ["Name" => "start", "Type" => "BIGINT"],
        ["Name" => "end", "Type" => "BIGINT"],
        ["Name" => "basepriceoverride", "Type" => "BIGINT", "Special" => "DEFAULT NULL"],
        ["Name" => "pricemodel", "Type" => "TEXT"],
        ["Name" => "collateralpremiumoverride", "Type" => "NUMERIC(8,4)", "Special" => "DEFAULT NULL"],
        ["Name" => "maxvolumeoverride", "Type" => "BIGINT", "Special" => "DEFAULT NULL"],
        ["Name" => "maxcollateraloverride", "Type" => "BIGINT", "Special" => "DEFAULT NULL"],
        ["Name" => "", "Type" => "", "Special" => "CONSTRAINT PK_ROUTES PRIMARY KEY (start, end)"],
        ["Name" => "", "Type" => "", "Special" => "CONSTRAINT CHK_Fixed_Price CHECK (pricemodel != 'Fixed' OR basepriceoverride IS NOT NULL)"]
    );

    $siteDatabase->register(
        "restrictedlocations",
        ["Name" => "id", "Type" => "BIGINT", "Special" => "primary key"],
        ["Name" => "type", "Type" => "TEXT"]
    );

?>