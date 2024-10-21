# Starter Kit Laravel

> **WARNING**: This starter kit is still in development. Do not use it in production.

## Universal Starter Kit

This starter kit is meant to be used with [Starter Kit Expo](https://github.com/spektasoft/starter-kit-expo).

## Local Development

1. In your `.env` file, make sure you use URL with `IP Address` instead of `localhost`:

    ```
    APP_URL={SERVER_IP_ADRESS_URL}
    # http://192.168.1.1:8000
    ```

    ```
    SANCTUM_STATEFUL_DOMAINS={CLIENT_IP_ADDRESS_URL}
    # Separated with a comma (,)
    # http://192.168.1.1:8000,http://192.168.1.2:8081
    ```

    ```
    VITE_HOST={SERVER_IP_ADRESS}
    # 192.168.1.1
    ```

    ```
    SESSION_DOMAIN={SERVER_IP_ADRESS}
    # 192.168.1.1
    ```

1. Run `artisan` with `--host`:
    ```
    php artisan serve --host 0.0.0.0
    ```

## License

The starter kit is open-sourced software licensed under the [MIT license](LICENSE).
