A lightweight PHP MVC framework. This is a refactored version of code initially developed at [Technorati](http://technorati.com) by [Matthew Levine](http://matthewlevine.com/), [Andrei Scheinkman](http://andreischeinkman.com/) and [Ryan King](http://theryanking.com/) with later development by [Stephen Handley](http://github.com/stephenhandley) and [Courtland Alves](http://courtlandalves.com/), who is currently leading the project.

Please see an [example implementation](http://github.com/temovico/temovico_example).

Here's a suggested httpd.conf (TBI):

    <VirtualHost *:80>
        # Add yoursite to your /etc/hosts file so you can
        # type it directly in your browser
        ServerName temovico.example
        ServerAdmin webmaster@website.com
        DocumentRoot /Library/WebServer/Documents/temovico_example/public

        RewriteEngine On
        RewriteRule ^/(?!((images)|(js)|(css))) /index.php
    </VirtualHost>