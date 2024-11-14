import axios from "axios";
import dotenv from "dotenv";

export const artisan = async (command: string) => {
    dotenv.config();

    const appUrl = process.env.APP_URL;
    const apiKey = process.env.API_KEY;

    const http = axios.create();
    const response = await http.post(`${appUrl}/api/v1/artisan/${command}`, {
        api_key: apiKey,
    });
    console.log(response.data);
};
