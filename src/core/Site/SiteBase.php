<!DOCTYPE html>
<html>
    <head>
    
        <link rel="icon" href="/resources/images/favicon.ico">
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="/resources/js/jquery-ui/jquery-ui.js"></script>
        <link href="/resources/js/jquery-ui/jquery-ui.css" rel="stylesheet">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        
        <?php 
            
            $this->renderCSRFToken();
            $this->renderMeta(); 
            
        ?>

    </head>
    <style>
        .background {
            bottom: 0%;
            left: 0;
            margin-bottom: 5vh;
            background-color: #333;
            height: 100%;
            width: 100%;
            overflow-y: auto;
        }
        
        .login-button {
            height: 32px;
        }

        .ui-autocomplete {
            max-height: 256px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: var(--bs-light);
        }
        
        <?php
        
            $this->renderStyle(); 
            
        ?>
        
    </style>
    <nav class="navbar navbar-expand-xl bg-dark navbar-dark sticky-top shadow">
        
        <div class="container-fluid">
        
            <?php
            
                $this->renderBadge(); 
                
            ?>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContents" aria-controls="navbarContents" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContents">
            
                <ul class="navbar-nav">
    
                    <?php 
                    
                        $this->renderNav(); 
                        
                    ?>
                
                </ul>
                
                <ul class="navbar-nav ms-auto">
                
                    <li class="nav-item me-4 mt-2 mb-2 border-start border-secondary">
                    </li>
                
                    <?php 
                    
                        $this->renderAuth(); 
                        
                    ?>
                
                </ul>
                
            </div>
        
        </div>
        
    </nav>
    <body class="background">
    
        <div class="container-fluid ps-4 pe-4">
            <br>
            
            <?php 
            
                $this->renderContent(); 
                
            ?>
            
        </div>
    
    </body>
</html>