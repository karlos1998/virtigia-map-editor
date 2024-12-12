import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import TailwindCssPrimeUi from 'tailwindcss-primeui';
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: ['selector', '[class*="app-dark"]'],
    plugins: [TailwindCssPrimeUi],
    theme: {
        screens: {
            sm: '576px',
            md: '768px',
            lg: '992px',
            xl: '1200px',
            '2xl': '1920px'
        }
    }
};
