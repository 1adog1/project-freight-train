<?php

    namespace Ridley\Views\Home;

    class Templates {
        
        protected function mainTemplate() {

            $this->errorTemplate();
            ?>
            
            <div class="text-light">

                <?php include __DIR__ . "/../../../config/frontPage.html"; ?>

            </div>

            <hr class="text-light mt-3">

            <form class="row text-light" method="post" action="/home/">

                <h3 class="mt-3">Hauling Calculator</h3>

                <div class="col-lg-3 mt-4">

                    <label for="origin" class="form-label">Origin</label>
                    <input type="text" class="form-control" name="origin" id="origin" value="<?php echo htmlspecialchars(($_POST["origin"] ?? "")); ?>" required>

                    <label for="destination" class="form-label mt-3">Destination</label>
                    <input type="text" class="form-control" name="destination" id="destination" value="<?php echo htmlspecialchars(($_POST["destination"] ?? "")); ?>" required>

                </div>
                <div class="col-lg-3 mt-4">
                    
                    <label for="volume" class="form-label">Volume</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="volume" id="volume" value="<?php echo htmlspecialchars(($_POST["volume"] ?? "")); ?>" required>
                        <span class="input-group-text">m³</span>
                    </div>
                    <label for="collateral" class="form-label mt-3">Collateral</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="collateral" id="collateral" value="<?php echo htmlspecialchars(($_POST["collateral"] ?? "")); ?>" required>
                        <span class="input-group-text">ISK</span>
                    </div>

                </div>
                <div class="col-lg-2 mt-4">

                    <?php if ($this->controller->allowRush): ?>

                        <div class="form-check form-switch" style="margin-top: 2.5rem !important;">
                            <input class="form-check-input" type="checkbox" role="switch" name="rush" id="rush" value="true" <?php echo isset($_POST["rush"]) ? "checked" : ""; ?>>
                            <label class="form-check-label" for="rush">Rush Delivery</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="margin-top: 3.225rem !important;">Generate Quote</button>
                    
                    <?php else: ?>

                        <button type="submit" class="btn btn-primary w-100" style="margin-top: 2rem !important;">Generate Quote</button>

                    <?php endif; ?>

                </div>
                <div class="col-lg-4">

                        <?php $this->resultsTemplate(); ?>
                    
                </div>
            </form>

            <hr class="text-light mt-3">

            <div class="row text-light mt-4">
                <div class="col-lg-3">
                    <h3>Standard Routes</h3>
                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->routeLister(); ?>

                    </ul>
                </div>
                <div class="col-lg-3">
                    <h3>General Limits</h3>
                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->limitsTemplate(); ?>

                    </ul>
                    <p class="text-danger fst-italic mt-2">* Some limits may be overridden by individual routes.</p>
                </div>
            </div>

            <hr class="text-light mt-3">
            
            <?php
        }

        protected function limitsTemplate() {
        ?>

            <li class="list-group-item bg-dark text-light">
                <b>Max Collateral: </b><?php echo htmlspecialchars(number_format($this->controller->maxCollateral)) . " ISK"; ?> *
            </li>
            <li class="list-group-item bg-dark text-light">
                <b>High Collateral Cutoff: </b><?php echo htmlspecialchars(number_format($this->controller->highCollateralCutoff)) . " ISK"; ?>
            </li>
            <li class="list-group-item bg-dark text-light">
                <b>Max Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxVolume)) . " m³"; ?> *
            </li>
            <li class="list-group-item bg-dark text-light">
                <b>Blockade Runner Cutoff: </b><?php echo htmlspecialchars(number_format($this->controller->blockadeRunnerCutoff)) . " m³"; ?>
            </li>
            <?php if ($this->controller->allowHighsecToHighsec): ?>

                <li class="list-group-item bg-dark text-light">
                    <b>Max Highsec ↔ Highsec Volume: </b><?php echo htmlspecialchars(number_format($this->controller->highsecToHighsecMaxVolume)) . " m³"; ?> *
                </li>

            <?php endif; ?>
            <?php if ($this->controller->allowWormholes): ?>

                <li class="list-group-item bg-dark text-light">
                    <b>Max Wormhole Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxWormholeVolume)) . " m³"; ?> *
                </li>

            <?php endif; ?>
            <?php if ($this->controller->allowPochven): ?>

                <li class="list-group-item bg-dark text-light">
                    <b>Max Pochven Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxPochvenVolume)) . " m³"; ?> *
                </li>

            <?php endif; ?>

        <?php
        }

        protected function routeLister() {
            
            foreach ($this->model->routes as $eachRoute) {
            ?>

                <li class="list-group-item bg-dark text-light fw-bold">
                    <?php echo htmlspecialchars($eachRoute["start"]); ?> → <?php echo htmlspecialchars($eachRoute["end"]); ?>
                </li>

            <?php
            }
            
        }

        protected function resultsTemplate() {

            if ($this->controller->quoteProcessed) :
            ?>
            
            <div class="card text-white bg-dark mt-4">
                <div class="card-body">
                    <h3 class="card-title mt-3">Hauling Quote</h3>

                    <p class="mt-3">
                        <b class="text-muted">Contract To — </b> <?php echo htmlspecialchars($this->controller->contractCorporation); ?><br>
                        <b class="text-muted">Destination — </b> <?php echo htmlspecialchars($this->controller->destinationString); ?><br>
                        <b class="text-muted">Collateral — </b> <?php echo htmlspecialchars($this->controller->collateralString); ?><br>
                        <b class="text-muted">Reward — </b> <?php echo htmlspecialchars($this->controller->priceString); ?><br>
                        <b class="text-muted">Expiration — </b> <?php echo htmlspecialchars($this->controller->expirationString); ?><br>
                        <b class="text-muted">Time to Complete — </b> <?php echo htmlspecialchars($this->controller->timeToCompleteString); ?><br>
                    </p>

                    <hr class="text-light mt-3">

                    <h4 class="card-subtitle mt-3">Price Breakdown</h4>
                    <small>
                        <p class="mt-3 mb-0">
                            <b class="text-muted">Price Model — </b> <?php echo htmlspecialchars($this->controller->priceModel); ?><br>
                            <b class="text-muted">Unit Price — </b> <?php echo htmlspecialchars($this->controller->unitPriceString); ?><br>
                            <b class="text-muted">Collateral Premium — </b> <?php echo htmlspecialchars($this->controller->collateralPremiumString); ?><br>
                            <b class="text-muted">Penalties:</b>
                            <div class="ms-4">
                                <?php 
                                    foreach ($this->controller->penalties as $eachType => $eachValue) {
                                        ?>
                                        <?php echo htmlspecialchars($eachType); ?>: <?php echo htmlspecialchars($eachValue); ?><br>
                                        <?php
                                    }
                                ?>
                            </div>
                        </p>
                    </small>
                </div>
            </div>
            
            <?php
            endif;
        }

        protected function errorTemplate() {
            
            foreach ($this->controller->errors as $eachError) {
            ?>

                <div class="alert alert-danger d-flex align-items-center mt-3" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($eachError); ?></div>
                </div>

            <?php
            }
            
        }
        
        protected function metaTemplate() {
            ?>
            
            <title><?php echo htmlspecialchars($this->serviceName); ?></title>
            <meta property="og:title" content="<?php echo htmlspecialchars($this->serviceName); ?>">
            <meta property="og:description" content="A hauling calculator powered by Project Freight Train.">
            <meta property="og:type" content="website">
            <meta property="og:url" content="<?php echo $_SERVER["SERVER_NAME"]; ?>">

            <script src="/resources/js/Home.js"></script>
            
            <?php
        }
        
    }

    class View extends Templates implements \Ridley\Interfaces\View {

        protected $model;
        protected $controller;
        protected $serviceName;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            $this->model = $this->dependencies->get("Model");
            $this->controller = $this->dependencies->get("Controller");
            $this->serviceName = $this->dependencies->get("Service Name");
        }
        
        public function renderContent() {
            
            $this->mainTemplate();
            
        }
        
        public function renderMeta() {
            
            $this->metaTemplate();
            
        }
        
    }

?>