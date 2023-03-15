<p>
<a href="https://packagist.org/packages/back2lobby/access-control"><img src="https://poser.pugx.org/back2lobby/access-control/d/total.svg" alt="Total Downloads"></a>
<a href="https://github.com/back2lobby/access-control/blob/master/LICENSE.txt"><img src="https://poser.pugx.org/back2lobby/access-control/license.svg" alt="License"></a>
</p>

AccessControl is a Laravel package for easy role & permission management with model-based role assignment and role-based permissions.
## Table of Contents

<details><summary>Click to expand</summary><p>

- [Introduction](#introduction)
- [Installation](#installation)
</p></details>

## Introduction

AccessControl simplifies role & permission management by enabling the assignment of roles based on models and defining role-based permissions for fine-grained control over user access.

Once installed, you can simply tell the access-control what you want to allow at the gate:

```php
// Give a role some permission
AccessControl::allow("editor")->to('edit');

// Assign role to any user
AccessControl::assign('editor')->to($user);

// You can also assign role for a specific roleable model
AccessControl::assign('editor')->to('edit',$post);

// Checking the permission on user for a roleable model
AccessControl::canUser($user)->do("edit",$post);

// Checking if the user has an editor role for that model
AccessControl::is($user)->an("editor",$post); 
```


## Installation

> **Note**: AccessControl requires PHP 8.1+ and Laravel 9.0+

1) Install AccessControl with composer:

    ```
    composer require back2lobby/access-control
    ```

2) Add AccessControl's trait to your user model:

    ```php
    use Back2Lobby\AccessControl\Traits\hasRolesAndPermissions;
    class User extends Model
    {
        use hasRolesAndPermissions;
    }
    ```

3) If you have a roleable model, then add AccessControl's trait to your roleable model:

    ```php
    use Back2Lobby\AccessControl\Traits\isRoleable;
    class Post extends Model
    {
        use isRoleable;
    }
    ```

4) Now, to run AccessControl's migrations. First publish the migrations into your app's `migrations` directory, by running the following command:

    ```
    php artisan vendor:publish --tag="access-control.migrations"
    ```

5) Finally, run the migrations:

    ```
    php artisan migrate
    ```

#### Facade

Whenever you use the `AccessControl` facade in your code, remember to add this line to your namespace imports at the top of the file:

```php
use AccessControl;
```

If your IDE is facing any issues with this facade, please use [barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper)
