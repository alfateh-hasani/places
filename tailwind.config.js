/**
 * Tailwind build for the public website (replaces the Play CDN).
 *
 * Build:  tailwindcss -c tailwind.config.js -i resources/css/front.css -o public/front/assets/css/tailwind.css --minify
 *         (or `npm run build:front`)
 *
 * The theme below is a 1:1 copy of the inline config the CDN used, including
 * `colors` replacing (not extending) the default palette.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        '!./resources/views/vendor/backpack/**',
        './resources/lang/**/*.php',
        './app/**/*.php',
        './public/front/assets/js/main.js',
        './public/front/assets/js/tel.js',
        './public/front/assets/js/map.js',
        './public/front/assets/js/jquery-searchbox.js',
        // Flowbite's JS (datepicker, dropdowns, modals) injects utility classes at runtime.
        './public/front/assets/vendor/flowbite-2.5.1/flowbite.min.js',
    ],
    theme: {
        container: {
            center: true,
        },
        colors: {
            'gri': '#f7bb8e',
            'white': '#fff',
            'black': '#000',
            'border': '#f7bb8e',
            'reviews': '#999999',
            'title': '#2C2C2C',
            'feature': '#343233',
            'feature-border': '#E8E8E8',
            'price': '#f7bb8e',
            'automated-1': 'rgba(239, 85, 44, .2)',
            'automated-2': 'rgba(255, 90, 95, .2)',
            'automated-3': 'rgba(233, 187, 113, .2)',
            'gritext': '#444',
            'commentbg': '#F4F6F8',
            'commentborder': '#F1F1F1',
            'blackopacity': 'rgba(0, 0, 0, .1)',
            'properits': '#F8F7FD',
            'filterbackground': '#fbfbfb',
            'filterborder': '#ececec',
            'filteritem': '#ebebe8',
            'filterhover': '#f7bb8e',
            'sort': '#f6f6f6',
            'sortactive': '#f7bb8e',
            'blue': '#0068CF',
            'footer': '#fcfcfc',
            'titletext': '#848484',
        },
    },
    plugins: [],
};
