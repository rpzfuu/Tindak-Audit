import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                inter: ["Inter", "sans-serif"],
            },
            fontWeight: {
                thin: 100,
                extraLight: 200,
                light: 300,
                normal: 400,
                medium: 500,
                semiBold: 600,
                bold: 700,
                extraBold: 800,
                black: 900,
            },
        },
    },

    plugins: [forms, require("daisyui")],
    daisyui: {
        themes: ["light"],
    },
};
