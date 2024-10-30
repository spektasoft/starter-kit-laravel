# Starter Kit Laravel

> **WARNING**: This starter kit is still in development. Do not use it in production.

## Universal Starter Kit

This starter kit is meant to be used with [Starter Kit Expo](https://github.com/spektasoft/starter-kit-expo).

## Local Development

1. Download this repository and extract it anywhere in your local environment.

1. Create the `.env` file:

    ```
    cp .env.example .env
    ```

1. Generate the `APP_KEY`:

    ```
    php artisan key:generate
    ```

1. Create the symbolic link for storage:

    ```
    php artisan storage:link
    ```

1. Run the app:

    ```
    composer run dev
    ```

To develop a universal app, follow the additional instructions below:

1. In your `.env` file, use URL with `IP Address` instead of `localhost`. Replace `IP Address` with your own:

    ```
    APP_URL={SERVER_IP_ADRESS_URL}
    # Example: http://192.168.1.1:8000
    ```

    ```
    SANCTUM_STATEFUL_DOMAINS={CLIENT_IP_ADDRESS_URL}
    # Separated with a comma (,)
    # Example: http://192.168.1.1:8081,http://192.168.1.2:8081
    ```

    ```
    VITE_HOST={SERVER_IP_ADRESS}
    # Example: 192.168.1.1 (without http)
    ```

    ```
    SESSION_DOMAIN={SERVER_IP_ADRESS}
    # Example: 192.168.1.1 (without http)
    ```

1. Run the app:
    ```
    composer run dev:host
    ```

## License

The starter kit is open-sourced software licensed under the [MIT license](LICENSE).
