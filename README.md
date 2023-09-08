# Alight-Admin
Alight-Admin is a quick admin panel extension based on the  [Alight framework](https://github.com/juneszh/alight).

![login screenshot](example/image/screenshot_login.png)

![console screenshot](example/image/screenshot_console.png)

## Features
* **No front-end coding required**. Built-in **Ant Design UI** (React) components and driven by PHP interface.
* Quickly build and easily configure **CRUD** pages with Table/From render.
* Includes **authorization**, **permissions** and **user management**.
* Customizable **Charts** displayed in the console by PHP, such as **Line**, **Column**, **Pie**, etc.

## Alight Family

| Project | Description |
| --- | --- |
| [Alight](https://github.com/juneszh/alight)  | Basic framework built-in routing, database, caching, etc. |
| [Alight-Admin](https://github.com/juneszh/alight-admin)  | A full admin panel extension based on Alight. No front-end coding required.|
| [Alight-Project](https://github.com/juneszh/alight-project) | A template for beginner to easily create web applications by Alight/Alight-Admin. |

## Requirements
PHP 7.4+

## Usage
Alight-Admin can be quickly built using [Alight-Project](https://github.com/juneszh/alight-project).
### Creating Project
```bash
$ composer create-project juneszh/alight-project {PROJECT_DIRECTORY} 
```

### Initialize Admin
The following commands will build the runtime environment required by **Alight-Admin**, such as installing composer package, inserting configuration options, creating database tables, and downloading front-end resources. Please make sure the [database has been configured](https://github.com/juneszh/alight#database).
```bash
$ cd {PROJECT_DIRECTORY} 
$ composer run admin-install
$ composer run admin-download
```

### Try the CRUD
Suppose we already have a database table: **admin_user**
| Name        | Datatype  | Default           |
| ----------- | --------- | ----------------- |
| id          | SMALLINT  | AUTO_INCREMENT    |
| account     | VARCHAR   |                   |
| password    | VARCHAR   |                   |
| name        | VARCHAR   |                   |
| email       | VARCHAR   |                   |
| role_id     | TINYINT   | 0                 |
| status      | TINYINT   | 1                 |
| auth_key    | VARCHAR   |                   |
| create_time | TIMESTAMP | CURRENT_TIMESTAMP |

Now, create a php table function under controller. For example: 

File: app/controller/admin/Test.php
```php
<?php
namespace ctr\admin;

use Alight\Admin\Auth;
use Alight\Admin\Form;
use Alight\Admin\Table;

class Test
{
    public static function userTable()
    {
        // Check the role_id from logged in user
        Auth::checkRole([1]); // Here '1' means only administrators have access

        // Create the table columns and search bar
        Table::column('id')->sort('ascend');
        Table::column('account')->search()->sort();
        Table::column('role_id')->search('select')->enum([1 => 'Administrator', 2 => 'Editor']);
        Table::column('name')->search();
        Table::column('email')->search();
        Table::column('status')->search('select')->enum([1 => 'Enable', 2 => 'Disable']);
        Table::column('create_time')->search('dateRange');

        // Create the buttons
        Table::button('add')->toolbar(); // Here 'toolbar()' means this button will be placed on toolbar
        Table::button('edit');
        Table::button('password')->danger();

        // Bind the database table 'admin_user' and render table page
        Table::render('admin_user');
    }
}
```
Then, we will get the table page as:

![table screenshot](example/image/screenshot_table.png)
*(In order to make the code more compact, some settings are omitted, such as: column title, status point, etc.)*

Next, we go ahead and create a form function:

```php
    public static function userForm()
    {
        // Ditto, Check the user access
        Auth::checkRole([1]);

        // Create the form fields
        Form::create('add'); // This form will bind the 'add' button
        Form::field('account')->required();
        Form::field('role_id')->type('select')->enum([1 => 'Administrator', 2 => 'Editor'])->required()->default(1);
        Form::field('name')->required();
        Form::field('email');
        Form::field('password')->type('password')->required();
        Form::field('confirm_password')->database(false)->type('password')->required()->confirm('password');

        // Create another form
        Form::create('edit')->copy('add'); // This form will bind the 'edit' button and copy fields from 'add'
        Form::field('password')->delete();
        Form::field('confirm_password')->delete();
        Form::field('status')->type('radio')->enum([1 => 'Enable', 2 => 'Disable']);

        // Create last form for 'password' button
        Form::create('password');
        Form::field('password')->type('password')->required();
        Form::field('confirm_password')->database(false)->type('password')->required()->confirm('password');

        // Bind the database table 'admin_user' and render form page
        Form::render('admin_user');
    }
```

Then, we will get the form page as:

![form screenshot](example/image/screenshot_form.png)

The last step, configure routing and side menu:

File: app/config/route/admin.php
```php
Route::get('test/user/table', [ctr\admin\Test::class, 'userTable'])->auth();
Route::any('test/user/form', [ctr\admin\Test::class, 'userForm'])->auth();
```

File: app/config/admin/menu.php
```php
Menu::item('Test');
Menu::subItem('User')->url('test/user/table');
```

Finally, the database table **user_admin** has completed **CRUD** creation

## Credits
* Composer requires
    * [juneszh/alight](https://github.com/juneszh/alight)
    * [gregwar/captcha](https://github.com/Gregwar/Captcha)
    * [symfony/var-exporter](https://github.com/symfony/var-exporter)
* UI components
    * [React](https://react.dev/)
    * [Vite](https://vitejs.dev/)
    * [Ant Design](https://ant.design/)
    * [Ant Design Pro Components](https://procomponents.ant.design/)
    * [Ant Design Charts](https://charts.ant.design/en)
    * [TinyMCE](https://www.tiny.cloud/tinymce/)
    * [fs-extra](https://github.com/jprichardson/node-fs-extra)
    * [react-resize-detector](https://github.com/maslianok/react-resize-detector)

## License
* [MIT license](./LICENSE)