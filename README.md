# Laravel Dev

A Laravel package that allows you to launch and manage all your development servers and commands in separate terminal windows with a single Artisan command.

## ✨ Features

-   🚀 Launch all your development servers & commands with a single command.
-   🖥️ Each process runs in a new, separate terminal window.
-   🎨 Customize terminal colors for each command for better visual organization.
-   ⚙️ Interactive menu to start, stop, or restart individual or all commands while running.
-   ✅ Cross-platform support for Windows, macOS, and Linux.

## 📋 Requirements

- **PHP** 8.1+
- **Laravel** 10.0+

## 📦 Installation

You can install the package via composer:

```bash
composer require maherremitadz/laravel-dev --dev
```

Next, publish the configuration file using the `vendor:publish` command. This will create a `config/laravel_dev.php` file in your project.

```bash
php artisan vendor:publish --provider="maherremitadz\LaravelDev\LaravelDevServiceProvider" --tag="config"
```

## Usage

### 1. ⚙️ Configure Your Commands

Open the `config/laravel_dev.php` file and define the commands you want to manage. You can add as many as you need.

```php
// config/laravel_dev.php
return [
    'commands' => [
        // Simple format
        'Laravel Server' => 'php artisan serve',
        'Queue Worker' => 'php artisan queue:work',

        // Advanced format with custom colors
        'Vite Dev Server' => [
            'command' => 'npm run dev --watch',
            'colors' => [
                'text' => 'Green',
                'background' => 'Black'
            ]
        ],
        'Reverb Server' => 'php artisan reverb:start',
    ],
];
```

### 2. 🚀 Run the Dev Command

Start the master command by running:

```bash
php artisan dev
```

This will launch all the commands defined in your configuration file, each in its own terminal window. You will then see an interactive menu in the original terminal allowing you to manage these processes.

### 3. ⚙️ Manage Your Processes

Once running, you can choose from the following actions:
-   `start command`: Start a configured command that is not currently running.
-   `stop command`: Stop a specific running command.
-   `stop all commands`: Stop all currently running commands.
-   `restart command`: Restart a specific running command.
-   `restart all commands`: Stop and then start all configured commands.


## 🎨 Available Colors

You can set the `text` and `background` colors for each terminal window.

-   **Windows**: `Black`, `DarkBlue`, `DarkGreen`, `DarkCyan`, `DarkRed`, `DarkMagenta`, `DarkYellow`, `Gray`, `DarkGray`, `Blue`, `Green`, `Cyan`, `Red`, `Magenta`, `Yellow`, `White`.
-   **macOS**: `black`, `white`, `red`, `green`, `blue`, `cyan`, `magenta`, `yellow`.
-   **Linux**: `Black`, `DarkBlue`, `DarkGreen`, `DarkCyan`, `DarkRed`, `DarkMagenta`, `Gray`, `DarkGray`, `Blue`, `Green`, `Cyan`, `Red`, `Magenta`, `Yellow`, `White`.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a pull request.

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/my-new-feature`).
3.  Make your changes.
4.  Commit your changes (`git commit -am 'Add some feature'`).
5.  Push to the branch (`git push origin feature/my-new-feature`).
6.  Create a new Pull Request.

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 🙏 Credits

- **Author:** [MaherRemitaDZ](https://github.com/MaherRemita)
- **Email:** maherr10203@gmail.com

---

⭐ **Found this package helpful? Give it a star!**