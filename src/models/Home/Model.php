<?php

    namespace Ridley\Models\Home;

    class Model implements \Ridley\Interfaces\Model {
        
        private $controller;
        private $databaseConnection;
        
        public $tiers = [];
        public $routes = [];

        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->controller = $this->dependencies->get("Controller");
            $this->databaseConnection = $this->dependencies->get("Database");

            $this->tiers = $this->loadTiers();
            $this->routes = $this->loadRoutes();

        }

        private function loadTiers() {

            $tierQuery = $this->databaseConnection->prepare("SELECT threshold, price FROM tiers ORDER BY threshold DESC");
            $tierQuery->execute();

            return (array)$tierQuery->fetchAll(\PDO::FETCH_ASSOC);

        }

        private function loadRoutes() {

            $routeQuery = $this->databaseConnection->prepare(
                "SELECT 
                    startsystem.name AS start, 
                    endsystem.name AS end
                FROM routes 
                INNER JOIN evesystems AS startsystem ON routes.start = startsystem.id 
                INNER JOIN evesystems AS endsystem ON routes.end = endsystem.id
                ORDER BY start ASC, end ASC"
            );
            $routeQuery->execute();

            return (array)$routeQuery->fetchAll(\PDO::FETCH_ASSOC);

        }
        
    }

?>