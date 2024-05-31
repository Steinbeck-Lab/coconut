import preset from './../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Dashboard/**/*.php',
        './resources/views/filament/dashboard/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/archilex/filament-filter-sets/**/*.php',
    ],
}
