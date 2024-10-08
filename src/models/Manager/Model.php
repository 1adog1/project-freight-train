<?php

    namespace Ridley\Models\Manager;

    class Model implements \Ridley\Interfaces\Model {
        
        private $controller;
        private $databaseConnection;
        
        public $tiers = [];
        public $regionRestrictions = [];
        public $systemRestrictions = [];
        public $routes = [];

        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->controller = $this->dependencies->get("Controller");
            $this->databaseConnection = $this->dependencies->get("Database");

            $this->tiers = $this->loadTiers();
            $this->regionRestrictions = $this->loadRestrictions("Region");
            $this->systemRestrictions = $this->loadRestrictions("System");
            $this->routes = $this->loadRoutes();

        }

        private function loadTiers() {

            $tierQuery = $this->databaseConnection->prepare("SELECT threshold, price FROM tiers ORDER BY threshold DESC");
            $tierQuery->execute();

            return (array)$tierQuery->fetchAll(\PDO::FETCH_ASSOC);

        }

        private function loadRestrictions($type) {

            if ($type == "Region") {
                $resultString = "evesystems.regionname";
                $joinString = "restrictedlocations.id = evesystems.regionid";
            }
            elseif ($type == "System") {
                $resultString = "evesystems.name";
                $joinString = "restrictedlocations.id = evesystems.id";
            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                throw new \Exception("An Incorrect Type was Passed.", 11002);
                return;
            }

            $restrictionQuery = $this->databaseConnection->prepare(
                "SELECT DISTINCT $resultString AS name
                FROM restrictedlocations 
                INNER JOIN evesystems ON $joinString
                WHERE type = :type
                ORDER BY name ASC"
            );
            $restrictionQuery->bindParam(":type", $type);
            $restrictionQuery->execute();

            return (array)$restrictionQuery->fetchAll(\PDO::FETCH_COLUMN);

        }


        private function loadRoutes() {

            $routeQuery = $this->databaseConnection->prepare(
                "SELECT 
                    startsystem.name AS start, 
                    endsystem.name AS end, 
                    routes.pricemodel AS model, 
                    routes.basepriceoverride AS price, 
                    routes.gatepriceoverride AS gateprice,
                    routes.minimumpriceoverride AS minimumprice,
                    routes.collateralpremiumoverride AS premium, 
                    routes.maxvolumeoverride AS maxvolume, 
                    routes.maxcollateraloverride AS maxcollateral,
                    routes.disablehighcollateral AS disablehighcollateral
                FROM routes 
                INNER JOIN  evesystems AS startsystem ON routes.start = startsystem.id 
                INNER JOIN evesystems AS endsystem ON routes.end = endsystem.id
                ORDER BY start ASC, end ASC"
            );
            $routeQuery->execute();

            return (array)$routeQuery->fetchAll(\PDO::FETCH_ASSOC);

        }
        
    }

?>