<?php

    namespace Ridley\Objects\Calculator;

    class Calculator {

        private $databaseConnection;
        private $versionVariables;
        private $esiHandler;
        private $optionsLoaded = false;

        //General Settings
        private $contractCorporation;
        //Restrictions
        private $onlyApprovedRoutes;
        private $allowHighsecToHighsec;
        private $allowLowsec;
        private $allowNullsec;
        private $allowWormholes;
        private $allowPochven;
        private $allowRush;
        //Timing Controls
        private $contractExpiration;
        private $contractTimeToComplete;
        private $rushContractExpiration;
        private $rushContractTimeToComplete;
        //Pricing Controls
        private $maxThresholdPrice;
        private $gatePrice;
        private $wormholePrice;
        private $pochvenPrice;
        private $minimumPrice;
        private $maximumPrice;
        private $minimumRushPremium;
        //Volume Controls
        private $maxVolume;
        private $blockadeRunnerCutoff;
        private $highsecToHighsecMaxVolume;
        private $maxWormholeVolume;
        private $maxPochvenVolume;
        //Collateral Controls
        private $maxCollateral;
        private $collateralPremium;
        //Collateral Penalty Controls
        private $highCollateralCutoff;
        private $highCollateralPenalty;
        private $highCollateralBlockadeRunnerPenalty;
        //Multiplier Controls
        private $rushMultiplier;
        private $nonstandardMultiplier;

        function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");
            $this->versionVariables = $this->dependencies->get("Version Variables");
            $this->esiHandler =  new \Ridley\Objects\ESI\Handler($this->databaseConnection, $this->versionVariables);

            $this->loadOptions();

        }

        public function getQuote($origin, $destination, $collateral, $volume, $rush = false) {

            $quote = new \Ridley\Objects\Calculator\Quote($this->contractCorporation);

            if (!$this->optionsLoaded) {
                $quote->errors[] = "No routing options configured. Please run the initial setup script.";
                return $quote;
            }

            $originData = $this->getSystemData($origin, $quote);
            $destinationData = $this->getSystemData($destination, $quote);

            if (is_null($originData) or is_null($destinationData)) {
                return $quote;
            }

            $routeQuery = $this->databaseConnection->prepare(
                "SELECT 
                    basepriceoverride, 
                    gatepriceoverride, 
                    minimumpriceoverride,
                    maximumpriceoverride,
                    minimumrushpremiumoverride,
                    pricemodel, 
                    collateralpremiumoverride, 
                    maxvolumeoverride, 
                    maxcollateraloverride, 
                    disablehighcollateral,
                    allowrushoverride,
                    contractexpirationoverride,
                    contracttimetocompleteoverride,
                    rushcontractexpirationoverride,
                    rushcontracttimetocompleteoverride,
                    rushmultiplieroverride
                FROM routes 
                WHERE start = :start AND end = :end"
            );
            $routeQuery->bindParam(":start", $originData["id"]);
            $routeQuery->bindParam(":end", $destinationData["id"]);
            $routeQuery->execute();

            $routeData = $routeQuery->fetch(\PDO::FETCH_ASSOC);

            $quote->nonStandard = ($routeData === false);
            $quote->routeOptions = (!$quote->nonStandard) ? $routeData : [];
            $quote->rushAllowed = $this->rushAllowed($quote);
            $quote->useRush = $rush;

            if ($collateral < 0 or $volume < 0) {

                $quote->errors[] = "Failed to Process Quote! Volume and collateral must be non-negative.";
                return $quote;

            }

            if ($quote->useRush and !$quote->rushAllowed) {

                $quote->errors[] = "Failed to Process Quote! The rush option is not permitted for this route.";
                return $quote;

            }

            if (!$this->checkVolume($originData, $destinationData, $volume, $quote)) {

                $quote->errors[] = "Failed to Process Quote! Max volume exceeded for your selected route or system combination.";
                return $quote;

            }

            if (!$this->checkCollateral($collateral, $quote)) {

                $quote->errors[] = "Failed to Process Quote! Max collateral exceeded.";
                return $quote;

            }

            $quote->destinationString = $destinationData["name"];
            $quote->volumeString = number_format($volume) . " m³";
            $quote->collateralString = number_format($collateral);
            $quote->standardExpiration = $quote->routeOptions["contractexpirationoverride"] ?? $this->contractExpiration;
            $quote->rushExpiration = $quote->routeOptions["rushcontractexpirationoverride"] ?? $this->rushContractExpiration;
            $quote->standardTimeToComplete = $quote->routeOptions["contracttimetocompleteoverride"] ?? $this->contractTimeToComplete;
            $quote->rushTimeToComplete = $quote->routeOptions["rushcontracttimetocompleteoverride"] ?? $this->rushContractTimeToComplete;

            //Route Calculation
            if (!$quote->nonStandard) {

                if ($quote->routeOptions["pricemodel"] == "Standard") {
                    $this->priceCheck($originData, $destinationData, $collateral, $volume, $quote);
                }
                elseif ($quote->routeOptions["pricemodel"] == "Fixed") {
                    $this->fixedPriceCheck($collateral, $volume, $quote);
                }
                elseif ($quote->routeOptions["pricemodel"] == "Range") {
                    $this->rangePriceCheck($originData, $destinationData, $collateral, $volume, $quote);
                }
                elseif ($quote->routeOptions["pricemodel"] == "Gate") {

                    if ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                        $this->wormholePriceCheck($collateral, $volume, $quote);
                    }
                    elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                        $this->pochvenPriceCheck($collateral, $volume, $quote);
                    }
                    else {
                        $this->gatePriceCheck($originData, $destinationData, $collateral, $volume, $quote);
                    }

                }
                else {
                    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                    throw new \Exception("Route uses an invalid model.", 12001);
                }

            }
            elseif ($this->onlyApprovedRoutes) {

                $quote->errors[] = "Failed to Process Quote! System is not an approved route.";
                return $quote;

            }
            //Non-Route Calculation
            elseif ($this->checkNonStandardRouteValidity($originData["id"], $destinationData["id"], $quote)) {

                if (!$this->allowHighsecToHighsec and $originData["class"] == "Highsec" and $destinationData["class"] == "Highsec") {

                    $quote->errors[] = "Failed to Process Quote! Highsec <-> Highsec routes are not permitted.";
                    return $quote;

                }

                $this->priceCheck($originData, $destinationData, $collateral, $volume, $quote);

            }

            return $quote;

        }

        private function priceCheck($originData, $destinationData, $collateral, $volume, &$quote) {

            if ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                $this->wormholePriceCheck($collateral, $volume, $quote);
            }
            elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                $this->pochvenPriceCheck($collateral, $volume, $quote);
            }
            elseif ($volume <= $this->blockadeRunnerCutoff or ($originData["class"] == "Highsec" and $destinationData["class"] == "Highsec")) {
                $this->gatePriceCheck($originData, $destinationData, $collateral, $volume, $quote);
            }
            else {
                $this->rangePriceCheck($originData, $destinationData, $collateral, $volume, $quote);
            }

        }

        private function rangePriceCheck($originData, $destinationData, $collateral, $volume, &$quote) {
            $coordinateDistance = (($destinationData["x"] - $originData["x"])**2 + ($destinationData["y"] - $originData["y"])**2 + ($destinationData["z"] - $originData["z"])**2)**(1/2);
            $distance = $coordinateDistance / 9460000000000000;
            $distanceString = number_format($distance, 2);

            $quote->priceModel = "Range - $distanceString LY";

            $tierQuery = $this->databaseConnection->prepare("SELECT price FROM tiers WHERE threshold >= :distance ORDER BY threshold ASC LIMIT 1");
            $tierQuery->bindParam(":distance", $distance);
            $tierQuery->execute();
            
            $tierPrice = $tierQuery->fetchColumn();

            if (isset($quote->routeOptions["basepriceoverride"])) {
                $standardPrice = $quote->routeOptions["basepriceoverride"];
            }
            elseif ($tierPrice !== false) {
                $standardPrice = $tierPrice;
            }
            else {
                $standardPrice = $this->maxThresholdPrice;
            }

            $quote->unitPriceString = number_format($standardPrice) . " ISK/m³";
            $basePrice = ($quote->routeOptions["basepriceoverride"] ?? $standardPrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $volume, $collateral, $quote);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $quote);

            $rushPrice = $this->getRushPrices($specialAdjustedPrice, $quote);

            $boundedStandardPrice = $this->adjustForBounding($specialAdjustedPrice, false, $quote);
            $boundedRushPrice = $this->adjustForBounding($rushPrice, true, $quote);

            $quote->standardPriceString = number_format($boundedStandardPrice);
            $quote->standardPrice = round($boundedStandardPrice);

            $quote->rushPriceString = number_format($boundedRushPrice);
            $quote->rushPrice = round($boundedRushPrice);

            $quote->valid = true;

        }

        private function gatePriceCheck($originData, $destinationData, $collateral, $volume, &$quote) {

            $routeCall = $this->esiHandler->call(
                endpoint: "/route/{origin_system_id}/{destination_system_id}/",
                origin_system_id: $originData["id"],
                destination_system_id: $destinationData["id"],
                retries: 1
            );

            if ($routeCall["Success"]) {

                if (!empty($routeCall["Data"])) {

                    $jumps = count($routeCall["Data"]["route"]);

                }
                else {

                    $quote->errors[] = "Error! Gate price check attempted on route with no gate connection!";
                    return;

                }

            }
            else {

                $quote->errors[] = "Error! Gate price check attempted on route with no gate connection!";
                return;

            }

            $quote->priceModel = "Gate - $jumps Jumps";

            $quote->unitPriceString = number_format(($quote->routeOptions["gatepriceoverride"] ?? $this->gatePrice)) . " ISK/Jump/m³";
            $basePrice = ($quote->routeOptions["gatepriceoverride"] ?? $this->gatePrice) * $jumps * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $volume, $collateral, $quote);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $quote);

            $rushPrice = $this->getRushPrices($specialAdjustedPrice, $quote);

            $boundedStandardPrice = $this->adjustForBounding($specialAdjustedPrice, false, $quote);
            $boundedRushPrice = $this->adjustForBounding($rushPrice, true, $quote);

            $quote->standardPriceString = number_format($boundedStandardPrice);
            $quote->standardPrice = round($boundedStandardPrice);

            $quote->rushPriceString = number_format($boundedRushPrice);
            $quote->rushPrice = round($boundedRushPrice);

            $quote->valid = true;

        }

        private function wormholePriceCheck($collateral, $volume, &$quote) {

            $quote->priceModel = "Wormhole";
            $quote->unitPriceString = number_format(($quote->routeOptions["basepriceoverride"] ?? $this->wormholePrice)) . " ISK/m³";
            $basePrice = ($quote->routeOptions["basepriceoverride"] ?? $this->wormholePrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $volume, $collateral, $quote);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $quote);

            $rushPrice = $this->getRushPrices($specialAdjustedPrice, $quote);

            $boundedStandardPrice = $this->adjustForBounding($specialAdjustedPrice, false, $quote);
            $boundedRushPrice = $this->adjustForBounding($rushPrice, true, $quote);

            $quote->standardPriceString = number_format($boundedStandardPrice);
            $quote->standardPrice = round($boundedStandardPrice);

            $quote->rushPriceString = number_format($boundedRushPrice);
            $quote->rushPrice = round($boundedRushPrice);

            $quote->valid = true;

        }

        private function pochvenPriceCheck($collateral, $volume, &$quote) {

            $quote->priceModel = "Pochven";
            $quote->unitPriceString = number_format(($quote->routeOptions["basepriceoverride"] ?? $this->pochvenPrice)) . " ISK/m³";
            $basePrice = ($quote->routeOptions["basepriceoverride"] ?? $this->pochvenPrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $volume, $collateral, $quote);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $quote);

            $rushPrice = $this->getRushPrices($specialAdjustedPrice, $quote);

            $boundedStandardPrice = $this->adjustForBounding($specialAdjustedPrice, false, $quote);
            $boundedRushPrice = $this->adjustForBounding($rushPrice, true, $quote);

            $quote->standardPriceString = number_format($boundedStandardPrice);
            $quote->standardPrice = round($boundedStandardPrice);

            $quote->rushPriceString = number_format($boundedRushPrice);
            $quote->rushPrice = round($boundedRushPrice);

            $quote->valid = true;

        }

        private function fixedPriceCheck($collateral, $volume, &$quote) {

            $quote->priceModel = "Fixed";
            $basePrice = $quote->routeOptions["basepriceoverride"];
            $quote->unitPriceString = number_format($basePrice) . " ISK";
            $adjustedPrice = $this->adjustForCollateral($basePrice, $volume, $collateral, $quote);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $quote);

            $rushPrice = $this->getRushPrices($specialAdjustedPrice, $quote);

            $boundedStandardPrice = $this->adjustForBounding($specialAdjustedPrice, false, $quote);
            $boundedRushPrice = $this->adjustForBounding($rushPrice, true, $quote);

            $quote->standardPriceString = number_format($boundedStandardPrice);
            $quote->standardPrice = round($boundedStandardPrice);

            $quote->rushPriceString = number_format($boundedRushPrice);
            $quote->rushPrice = round($boundedRushPrice);

            $quote->valid = true;

        }

        private function adjustForBounding($checkPrice, $forRush, &$quote) {

            $minimumPriceToUse = $quote->routeOptions["minimumpriceoverride"] ?? $this->minimumPrice;
            $maximumPriceToUse = $quote->routeOptions["maximumpriceoverride"] ?? $this->maximumPrice;

            if (
                $forRush == $quote->useRush
                and $checkPrice < $minimumPriceToUse
            ) {
                $quote->penalties["Minimum Price"] = number_format($minimumPriceToUse) . " ISK";
            }

            if (
                $forRush == $quote->useRush
                and $checkPrice > $maximumPriceToUse
            ) {
                $quote->penalties["Maximum Price"] = number_format($maximumPriceToUse) . " ISK";
            }

            return min(
                $maximumPriceToUse, 
                max(
                    $minimumPriceToUse, 
                    $checkPrice
                )
            );

        }

        private function getRushPrices($standardPrice, &$quote) {

            $minimumRushPremiumToUse = $quote->routeOptions["minimumrushpremiumoverride"] ?? $this->minimumRushPremium;
            $actualRushMultiplier = $quote->routeOptions["rushmultiplieroverride"] ?? $this->rushMultiplier;

            $rushPrice = max(
                ($standardPrice + $minimumRushPremiumToUse),
                ($standardPrice * $actualRushMultiplier)
            );

            if ($quote->useRush) {

                $quote->penalties["Rush"] = number_format($actualRushMultiplier, 4) . "×";

                if (($standardPrice + $minimumRushPremiumToUse) > ($standardPrice * $actualRushMultiplier)) {

                    $quote->penalties["Minimum Rush Premium"] = number_format($minimumRushPremiumToUse) . " ISK";

                }

            }

            return $rushPrice;

        }

        private function adjustForSpecialMultipliers($adjustedPrice, &$quote) {

            if ($quote->nonStandard) {
                $quote->penalties["Non-Standard"] = number_format($this->nonstandardMultiplier, 4) . "×";
            }

            $adjustedForSpecialMultipliersPrice = ($adjustedPrice * (($quote->nonStandard) ? $this->nonstandardMultiplier : 1));

            return $adjustedForSpecialMultipliersPrice;

        }

        private function adjustForCollateral($basePrice, $volume, $collateral, &$quote) {

            $percentage = $quote->routeOptions["collateralpremiumoverride"] ?? $this->collateralPremium;
            $premiumMultiplier = $percentage / 100;

            if ($collateral > $this->highCollateralCutoff and !(!$quote->nonStandard and boolval($quote->routeOptions["disablehighcollateral"]))) {

                $basePremium = $this->highCollateralCutoff * $premiumMultiplier;
                $highCollateralMagnitude = ($volume < $this->blockadeRunnerCutoff) ? $this->highCollateralBlockadeRunnerPenalty : $this->highCollateralPenalty;
                $highCollateralMultiplier = ceil(($collateral - $this->highCollateralCutoff) / $this->highCollateralCutoff);
                $totalHighCollateral = $highCollateralMagnitude * $highCollateralMultiplier;
                $totalPremium = $basePremium + $totalHighCollateral;

                $quote->collateralPremiumString = number_format($basePremium) . " ISK";
                $quote->penalties["High Collateral"] = "+" . number_format($totalHighCollateral) . " ISK";

            }
            else {

                $totalPremium = $collateral * $premiumMultiplier;

                $quote->collateralPremiumString = number_format($totalPremium) . " ISK";

            }

            return $basePrice + $totalPremium;

        }

        private function rushAllowed(&$quote) {

            if ($quote->nonStandard) {

                return $this->allowRush;

            }
            else {

                return (
                    (
                        $this->allowRush
                        and $quote->routeOptions["allowrushoverride"] != "Disallow"
                    )
                    or $quote->routeOptions["allowrushoverride"] == "Allow"
                );

            }

        }

        private function checkCollateral($collateral, &$quote) {

            if (isset($quote->routeOptions["maxcollateraloverride"])) {
                return $collateral <= $quote->routeOptions["maxcollateraloverride"];
            }
            else {
                return $collateral <= $this->maxCollateral;
            }

        }

        private function checkVolume($originData, $destinationData, $volume, &$quote) {

            if (isset($quote->routeOptions["maxvolumeoverride"])) {
                return $volume <= $quote->routeOptions["maxvolumeoverride"];
            }
            elseif ($originData["class"] == "Highsec" and $destinationData["class"] == "Highsec") {
                return $volume <= $this->highsecToHighsecMaxVolume;
            }
            elseif ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                return $volume <= $this->maxWormholeVolume;
            }
            elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                return $volume <= $this->maxPochvenVolume;
            }
            else {
                return $volume <= $this->maxVolume;
            }

        }

        private function checkNonStandardRouteValidity($start, $end, &$quote) {

            $allowedClassList = ["'Highsec'"];

            if ($this->allowLowsec) {
                $allowedClassList[] = "'Lowsec'";
            }
            if ($this->allowNullsec) {
                $allowedClassList[] = "'Nullsec'";
            }
            if ($this->allowWormholes) {
                $allowedClassList[] = "'Wormhole'";
            }
            if ($this->allowPochven) {
                $allowedClassList[] = "'Pochven'";
            }

            $allowedClasses = implode(", ", $allowedClassList);

            $queryString = "SELECT COUNT(*) FROM evesystems WHERE 
                (
                    class IN ($allowedClasses) 
                    AND (
                        NOT EXISTS (SELECT id FROM allowedlocations)
                        OR id IN (SELECT id FROM allowedlocations WHERE type = 'System') 
                        OR regionid IN (SELECT id FROM allowedlocations WHERE type = 'Region')
                    )
                    AND id NOT IN (SELECT id FROM restrictedlocations WHERE type = 'System') 
                    AND regionid NOT IN (SELECT id FROM restrictedlocations WHERE type = 'Region')
                )
                AND (
                    id = :start_id
                    OR id = :end_id
                )
            ";

            try {

                $systemQuery = $this->databaseConnection->prepare($queryString);
                $systemQuery->bindParam(":start_id", $start);
                $systemQuery->bindParam(":end_id", $end);
                $systemQuery->execute();

                $count = $systemQuery->fetchColumn();

                if ($count !== false and $count === 2) {
                    return true;
                }
                else {
                    $quote->errors[] = "One or both systems are not approved for non-standard routes!";
                    return false;
                }

            }
            catch (\Exception $error) {
                $quote->errors[] = "One or both systems are not approved for non-standard routes! " . $error->getMessage();
                return false;
            }

        }

        private function getSystemData($name, &$quote) {

            try {

                $systemQuery = $this->databaseConnection->prepare("SELECT id, name, class, x, y, z FROM evesystems WHERE name = :name LIMIT 1");
                $systemQuery->bindParam(":name", $name);
                $systemQuery->execute();

                $result = $systemQuery->fetch(\PDO::FETCH_ASSOC);

                if ($result !== false) {
                    return $result;
                }
                else {
                    $quote->errors[] = "The System $name Was Not Found!";
                    return;
                }

            }
            catch (\Exception $error) {
                $quote->errors[] = "The System $name Was Not Found! " . $error->getMessage();
                return;
            }

        }

        private function loadOptions() {
            
            $optionQuery = $this->databaseConnection->prepare("SELECT * FROM options ORDER BY iteration DESC LIMIT 1");
            $optionQuery->execute();
            $optionData = $optionQuery->fetch(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                //General Settings
                $this->contractCorporation = $optionData["contractcorporation"];
                //Restrictions
                $this->onlyApprovedRoutes = boolval($optionData["onlyapprovedroutes"]);
                $this->allowHighsecToHighsec = boolval($optionData["allowhighsectohighsec"]);
                $this->allowLowsec = boolval($optionData["allowlowsec"]);
                $this->allowNullsec = boolval($optionData["allownullsec"]);
                $this->allowWormholes = boolval($optionData["allowwormholes"]);
                $this->allowPochven = boolval($optionData["allowpochven"]);
                $this->allowRush = boolval($optionData["allowrush"]);
                //Timing Controls
                $this->contractExpiration = (int)$optionData["contractexpiration"];
                $this->contractTimeToComplete = (int)$optionData["contracttimetocomplete"];
                $this->rushContractExpiration = (int)$optionData["rushcontractexpiration"];
                $this->rushContractTimeToComplete = (int)$optionData["rushcontracttimetocomplete"];
                //Pricing Controls
                $this->maxThresholdPrice = (int)$optionData["maxthresholdprice"];
                $this->gatePrice = (int)$optionData["gateprice"];
                $this->wormholePrice = (int)$optionData["wormholeprice"];
                $this->pochvenPrice = (int)$optionData["pochvenprice"];
                $this->minimumPrice = (int)$optionData["minimumprice"];
                $this->maximumPrice = (int)$optionData["maximumprice"];
                $this->minimumRushPremium = (int)$optionData["minimumrushpremium"];
                //Volume Controls
                $this->maxVolume = (int)$optionData["maxvolume"];
                $this->blockadeRunnerCutoff = (int)$optionData["blockaderunnercutoff"];
                $this->highsecToHighsecMaxVolume = (int)$optionData["highsectohighsecmaxvolume"];
                $this->maxWormholeVolume = (int)$optionData["maxwormholevolume"];
                $this->maxPochvenVolume = (int)$optionData["maxpochvenvolume"];
                //Collateral Controls
                $this->maxCollateral = (int)$optionData["maxcollateral"];
                $this->collateralPremium = (float)$optionData["collateralpremium"];
                //Collateral Penalty Controls
                $this->highCollateralCutoff = (int)$optionData["highcollateralcutoff"];
                $this->highCollateralPenalty = (int)$optionData["highcollateralpenalty"];
                $this->highCollateralBlockadeRunnerPenalty = (int)$optionData["highcollateralblockaderunnerpenalty"];
                //Multiplier Controls
                $this->rushMultiplier = (float)$optionData["rushmultiplier"];
                $this->nonstandardMultiplier = (float)$optionData["nonstandardmultiplier"];

                $this->optionsLoaded = true;
            }
            else {
                $this->optionsLoaded = false;
            }

        }
        
    }

?>