# Starter Kit Laravel

> **WARNING**: This starter kit is still in development. Do not use it in production.

## Universal Starter Kit

This starter kit is meant to be used with [Starter Kit Expo](https://github.com/spektasoft/starter-kit-expo).

## Local Development

If you want to develop a universal app, follow the instructions below. Otherwise, you can run the Laravel `artisan serve` command as usual.

To run a universal app:

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

1. Run this `npm` command:
    ```
    npm run serve:host
    ```

## License

The starter kit is open-sourced software licensed under the [MIT license](LICENSE).
