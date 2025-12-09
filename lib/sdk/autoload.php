<?php

/**
 * SPL Autoloader pentru namespace-ul Ovesio
 * Incarca clasele din folderul src/
 */
spl_autoload_register(function ($className) {
    // Verificăm dacă clasa aparține namespace-ului Ovesio
    $namespace = 'Ovesio\\';

    if (strpos($className, $namespace) === 0) {
        // Eliminăm namespace-ul de bază
        $relativeClass = substr($className, strlen($namespace));

        // Convertim namespace separators la directory separators
        $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);

        // Construim calea completă către fișier
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relativeClass . '.php';

        // Verificăm dacă fișierul există și îl includem
        if (file_exists($file)) {
            require_once $file;
        }
    }
});