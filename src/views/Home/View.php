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

            <form class="row text-light justify-content-center" method="post" action="/home/">

                <div class="col-lg-3 mt-3">

                    <label for="origin" class="form-label">Origin</label>
                    <input type="text" class="form-control" name="origin" id="origin" value="<?php echo htmlspecialchars(($_POST["origin"] ?? "")); ?>" required>

                    <label for="destination" class="form-label mt-3">Destination</label>
                    <input type="text" class="form-control" name="destination" id="destination" value="<?php echo htmlspecialchars(($_POST["destination"] ?? "")); ?>" required>

                </div>
                <div class="col-lg-3 mt-3">
                    
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
                <div class="col-lg-2 mt-3">

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
            </form>

            <?php $this->resultsTemplate(); ?>

            <hr class="text-light mt-4">

            <div class="row text-light mt-4">
                <div class="col-lg-3">
                    <h3>Standard Routes</h3>
                    <ul class="list-group small" style="margin-top: 2rem !important;">

                        <?php $this->routeLister(); ?>

                    </ul>
                    <p class="text-danger fst-italic mt-2"><i class="bi bi-database-fill-gear"></i> Routes may override volume and collateral limits.</p>
                </div>
                <div class="col-lg-3">
                    <h3>General Volume Limits</h3>
                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->volumeLimitsTemplate(); ?>

                    </ul>
                </div>
                <div class="col-lg-3">
                    <h3>Special Volume Limits</h3>
                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->specialVolumeLimitsTemplate(); ?>

                    </ul>
                </div>
                <div class="col-lg-3">
                    <h3>Collateral Limits</h3>
                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->collateralLimitsTemplate(); ?>

                    </ul>
                </div>
            </div>

            <hr class="text-light mt-3">
            
            <?php
        }

        protected function volumeLimitsTemplate() {
        ?>

            <li class="list-group-item bg-dark text-light">
                <b>Max Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxVolume)) . " m³"; ?>
            </li>
            <li class="list-group-item bg-dark text-light">
                <b>Blockade Runner Cutoff: </b><?php echo htmlspecialchars(number_format($this->controller->blockadeRunnerCutoff)) . " m³"; ?>
            </li>

        <?php
        }

        protected function specialVolumeLimitsTemplate() {
            ?>
    
                <?php if ($this->controller->allowHighsecToHighsec): ?>
    
                    <li class="list-group-item bg-dark text-light">
                        <b>Max Highsec ↔ Highsec Volume: </b><?php echo htmlspecialchars(number_format($this->controller->highsecToHighsecMaxVolume)) . " m³"; ?>
                    </li>
    
                <?php endif; ?>
                <?php if ($this->controller->allowWormholes): ?>
    
                    <li class="list-group-item bg-dark text-light">
                        <b>Max Wormhole Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxWormholeVolume)) . " m³"; ?>
                    </li>
    
                <?php endif; ?>
                <?php if ($this->controller->allowPochven): ?>
    
                    <li class="list-group-item bg-dark text-light">
                        <b>Max Pochven Volume: </b><?php echo htmlspecialchars(number_format($this->controller->maxPochvenVolume)) . " m³"; ?>
                    </li>
    
                <?php endif; ?>
    
            <?php
            }

        protected function collateralLimitsTemplate() {
        ?>

            <li class="list-group-item bg-dark text-light">
                <b>Max Collateral: </b><?php echo htmlspecialchars(number_format($this->controller->maxCollateral)) . " ISK"; ?>
            </li>
            <li class="list-group-item bg-dark text-light">
                <b>High Collateral Cutoff: </b><?php echo htmlspecialchars(number_format($this->controller->highCollateralCutoff)) . " ISK"; ?>
            </li>

        <?php
        }

        protected function routeLister() {
            
            foreach ($this->model->routes as $eachRoute) {
            ?>

                <li class="list-group-item bg-dark text-light">
                    <div class="row">
                        <div class="col-lg-7 fw-bold">
                            <?php echo htmlspecialchars($eachRoute["Start"]); ?> → <?php echo htmlspecialchars($eachRoute["End"]); ?>
                        </div>
                        <div class="col-lg-4">
                            <?php echo htmlspecialchars($eachRoute["Model"]) . " Pricing"; ?>
                        </div>
                        <?php if (!empty($eachRoute["Overrides"])): ?>
                            <div class="col-lg-1">
                                <a class="override-popover text-danger"  tabindex="0" data-bs-toggle="popover" data-bs-placement="right" data-bs-html="true" title="<?php echo htmlspecialchars($eachRoute["Start"]); ?> → <?php echo htmlspecialchars($eachRoute["End"]); ?> Overrides" data-bs-content="<?php echo implode("<br>", $eachRoute["Overrides"]); ?>"><i class="bi bi-database-fill-gear"></i></a><br>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>

            <?php
            }
            
        }

        protected function resultsTemplate() {

            if ($this->controller->quoteProcessed) :
            ?>
            
            <div class="row justify-content-center mt-2">
                <div class="col-lg-6">
                    <div class="card text-white bg-dark mt-4 border-secondary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 ps-4 border-end border-secondary">
                                    <h3 class="card-title mt-3">Hauling Quote</h3>

                                    <p class="mt-3">
                                        <b class="text-muted">Contract To — </b> <?php echo htmlspecialchars($this->controller->contractCorporation); ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->contractCorporation); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                        <b class="text-muted">Destination — </b> <?php echo htmlspecialchars($this->controller->destinationString); ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->destinationString); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                        <b class="text-muted">Collateral — </b> <?php echo htmlspecialchars($this->controller->collateralString) . " ISK"; ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->collateralString); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                        <b class="text-muted">Reward — </b> <?php echo htmlspecialchars($this->controller->priceString) . " ISK"; ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->priceString); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                        <b class="text-muted">Expiration — </b> <?php echo htmlspecialchars($this->controller->expirationString) . " Days"; ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->expirationString); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                        <b class="text-muted">Time to Complete — </b> <?php echo htmlspecialchars($this->controller->timeToCompleteString) . " Days"; ?>
                                         <a data-copy-value="<?php echo htmlspecialchars($this->controller->timeToCompleteString); ?>" class="bi bi-clipboard2 copy-out text-muted" data-bs-toggle="popover" data-bs-placement="right" title="Copied!"></a><br>
                                    </p>

                                </div>
                                <div class="col-lg-6 ps-4"> 
                                    <h3 class="card-title mt-3">Price Breakdown</h3>
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
                                </div>
                            </div>
                        </div>
                    </div>
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
            <script src="https://cdn.jsdelivr.net/npm/@floating-ui/core@1.6.0"></script>
            <script src="https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.6.3"></script>
            
            <?php
        }

        protected function styleTemplate() {
            ?>
            
            .copy-out, .override-popover {
                border-bottom: dotted 1px;
            }

            .copy-out:hover, .override-popover:hover {
                cursor: pointer;
            }
            
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

        public function renderStyle() {
            
            $this->styleTemplate();
            
        }
        
    }

?>