# Phalcon project template

This template aims to provide a quick start for any [Phalcon](https://docs.phalcon.io/5.8/introduction/) project.

It uses [Docker Compose](https://docs.docker.com/compose/) and Makefile in order to prevents file ownership & permission errors caused by Composer package installation.

## Quickstart

To start a Phalcon project this way

1. Hit the <kbd>Use this template</kbd> button in GitHub,
2. Clone your repository on your machine,
3. _Optional._ Customize your compose project name at top of [compose.yaml](https://github.com/senorihl/phalcon-project/blob/7d0e24fb3adfcadd13f30483e6d7cf6e470e1e3b/compose.yaml#L1) file,
4. Run `make start`, it will build the image and run the containers,
5. Open `http://[compose project name].localhost` 🎉

## Further notes

### PHP dependencies
  
You can customize your PHP dependencies in the [Dockerfile](https://github.com/senorihl/phalcon-project/blob/7d0e24fb3adfcadd13f30483e6d7cf6e470e1e3b/docker/Dockerfile#L31-L43).

### Phalcon module

Since Phalcon module aren't totally isolated by each others, you have to explicitly provide all routes/resources use by the specific modules.

For example, if you want a OAuth module to handle your authentication you have to

1. Add the controllers and the logic in the desired namespace (here OAuth) with the related module which could look like this:
   ```php
   <?php
   
   # src/OAuth/Module.php
   
   namespace App\OAuth;
   
   use App\Api\Plugin\ResponseFormatter;
   use Phalcon\Di\DiInterface;
   use Phalcon\Mvc\Application;
   use Phalcon\Mvc\Dispatcher;
   use Phalcon\Mvc\ModuleDefinitionInterface;
   
   class Module implements ModuleDefinitionInterface {
   
       public function registerAutoloaders(DiInterface $container = null)
       {
           // empty since we use composer autoloader
       }
   
       public function registerServices(DiInterface $container)
       {
           // Add this to disable automatic views
           $container->get('eventsManager')->attach('application', $this);
   
           // Add this (already used by Api module) in order to 
           // transform you actions returned data into JSON
           $container->get('eventsManager')->attach('dispatch', new ResponseFormatter());
   
           // Add this to declare all necessarily setting for your controllers
           $container->setShared('dispatcher', function () use ($container) {
               $dispatcher = new Dispatcher();
               $dispatcher->setEventsManager($container->get('eventsManager'));
               $dispatcher->setControllerSuffix('Controller');
               $dispatcher->setDefaultNamespace('\\App\\OAuth\\Controller\\');
               return $dispatcher;
           });
       }
   
       public function afterStartModule($_, Application $application)
       {
           // add this since we will be using JSON
           $application->useImplicitView(false);
       }
   } 
   ```
2. Declare your module:
   ```php
   <?php
   
   # src/services.php
   
   # Add these imports 
   use App\OAuth\Module as OAuthModule;
   use App\OAuth\Controller as OAuthController;
   
   # Add these in the router service declaration
   
   $container->set('router', function () use ($container, $defaultModule) {
       # ...
       switch ($defaultModule) {
           case 'web':
               $router->addModuleResource('web', WebController\IndexController::class);
               $router->addModuleResource('web', WebController\ErrorController::class);
               break;
           case 'api':
               $router->addModuleResource('api', ApiController\IndexController::class);
               $router->addModuleResource('api', ApiController\ErrorController::class);
               break;
           case 'oauth':
               $router->addModuleResource('oauth', OAuthController\IndexController::class);
               # You can add more, but my advice is to keep all of them 
               # outside of subdirectories of the src/OAuth/Controller
               break;
       }
       # ...
   }
   ```
3. As you can see we use a `$defaultModule` variable, 
   it's defined by the `PHALCON_MODULE` environment variable 
   which defaults to `web` in the Nginx configuration which is
   defined by the `X-Phalcon-Module` header.
   
   To handle this you just have to either declare the environment variable 
   in your docker container by adding a service in the compose.yaml file (or specialized docker image as in the runnable target [here](https://github.com/senorihl/phalcon-project/blob/7d0e24fb3adfcadd13f30483e6d7cf6e470e1e3b/docker/Dockerfile#L87))
   or declare the header using Traefik configuration labels in the compose.yaml like so:
   ```yaml
   services:
   # ...
       php:
       # ...
           labels:
           # Add these to declare a subdomain, other option may be used (liek path prefix but not tested)
           - "traefik.http.routers.oauth-${COMPOSE_PROJECT_NAME}.middlewares=oauth-${COMPOSE_PROJECT_NAME}"
           - "traefik.http.routers.oauth-${COMPOSE_PROJECT_NAME}.rule=Host(`oauth.${COMPOSE_PROJECT_NAME}.localhost`)"
           - "traefik.http.middlewares.oauth-${COMPOSE_PROJECT_NAME}.headers.customrequestheaders.X-Phalcon-Module=web"
   ```
   


### Production image

You can use the [runnable target](https://github.com/senorihl/phalcon-project/blob/7d0e24fb3adfcadd13f30483e6d7cf6e470e1e3b/docker/Dockerfile#L85) of the Dockerfile to do so.
It's totally standalone for each configured Phalcon module.

### What's next ?

- [x] Add an assets builder like Webpack [#1](https://github.com/senorihl/phalcon-project/pull/1)
- [ ] Add an console interpreter (using `symfony/console` which is far more understandable than the built-in system)
- [ ] Add an database migration system
- [ ] _More to come..._

