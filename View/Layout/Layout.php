<?php

require_once "/../View.php";
class Layout extends View{
    function body($params = [])
    { ?>
        <!DOCTYPE html>
        <html>
            <head>
                <?php
                $this->meta();
                $this->css();
                $this->head_scripts();
                ?>
            </head>
            <body>
            <?php
            $this->children["content"]->body($params);
            ?>
            </body>
        </html>
    <?php }
}