<p align="center"><a href="https://aocmap.essclients.com" target="_blank"><img src="https://aocmap.essclients.com/images/logo.png" width="400"></a></p>

### [FAQ](https://github.com/Excel-Software-Services/aoc-map-tool/blob/master/docs/faq.md)


## Installation & Deployment

- Take a pull of respective branch on the server and follow the following commands:

```sh
sh deploy.sh
```

- If it is first installation then you have to run seeder to fill the default data the following commands

```sh
php artisan db:seed
```

- If its just deployment then run respective or remaining seeder:

```sh
php artisan db:seed --class=SeederClassName
```

## LaunchDarkly Usage

- Create the feature flage from the Launch Darkly plateform
- Now we use it following :

-PHP

```php
use App\Helpers\LD;

if (LD::checkFeatureFlag('feature-flag-key')) {
//Code which match the condition
}else{
//older code or what we need to show when the feature flag is off
}
```

-JS

```js
LD.checkFeatureFlag(
  "feature-flag-key",
  function () {
    console.log("Flag ON");
  },
  function () {
    console.log("Flag OFF");
  }
);
```

## Modal Popup Usage

```html
<x-modal>
  <x-slot name="id"> <!-- Modal ID --> </x-slot>
  <x-slot name="title"><!-- Modal Title --></x-slot>
  <x-slot name="class"><!-- Additional classes if needed --></x-slot>
  <x-slot name="body">
    <!-- Modal Body: Content, Form etc... -->
  </x-slot>
  <x-slot name="footer">
    <!-- Footer of the modal -->
  </x-slot>
</x-modal>
```

-OR

```html
@component('components.modal', [ 'id' => 'modal ID', 'class' => 'additional
classes', ]) @slot('title', 'Title') @slot('body')
<!-- Modal Body: Content, Form etc... -->
@endslot @slot('footer')
<!-- Footer of the modal -->
@endslot @endcomponent
```

Set trigger to open Modal popup like:

```html
<a data-target="#ID_OF_MODAL_POPUP" data-toggle="modal" href="#">Open Popup</a>
```

## Permission Module Instruction

Here we can add steps for permission usage

## Check Coding Standards Locally

- Here we can check our all files locally with following steps

  - Check the PSR standard

  ```sh
     composer global require "squizlabs/php_codesniffer=*"

     /home/YOUR_USERNAME/.config/composer/vendor/bin/phpcs --standard=PSR12 app
  ```

  It will install the global tool and next command will check standard of the app folder

  - Execute Code Static Analysis (PHP Stan + Larastan) Locally

  ```sh
     composer global require nunomaduro/larastan

     /home/YOUR_USERNAME/.config/composer/vendor/bin/phpstan analyse app -c ./vendor/nunomaduro/larastan/extension.neon  --level=4
  ```

## Frequently Use Commands

```sh
npm install

#For CSS and JS Deplyoment
alias npm-exec='PATH=$(npm bin):$PATH'
npm-exec mix --production

#Migration
php artisan migrate
```

## Note Types

### tracker
Notes related to a recruitertracker record.  They document the recruiting process of a candidate.
They link to the tracker record,  the patient,  and candidate.

## Guidelines on Asset and Dependency Management

### [AOC Asset & Dependency Tracker](https://docs.google.com/spreadsheets/d/10MhdSPCeSL0qO18hx9vAZ3P4RHfESYeNTCW3KqPWX08/edit?usp=sharing)

Here we can put the policy for how we will track the assets and dependencies.

- How do we track the versions of the various components
- How often should we update or confirm that we are on the latest version
- What about scanning?

