// tailwind.config.js
import preset from './vendor/filament/filament/tailwind.config.js'

export default {
    // Hereda la configuración base (colores, tipografía, etc.) de Filament
    presets: [preset],

    // Rutas que Tailwind debe escanear para encontrar clases (purgar CSS)
    content: [
        './app/Providers/**/*.php',
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php', // Base de Filament

        // 🚨 CRUCIAL: Asegura que el contenido del panel de empleados se incluya
        './app/Filament/Employee/**/*.php',
        './app/Filament/Pages/VerifyPaymentPage.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}