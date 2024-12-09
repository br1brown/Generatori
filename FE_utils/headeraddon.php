<?php
require_once __DIR__.'/funzioni.php';

// Array per memorizzare i dati dei generatori.
$generatori = caricaGeneratori($service); 
$multiplo = (count($generatori) > 1);

$meta->title = $multiplo? $settings['AppName']: $generatori[0]['nome'];
$meta->description = $multiplo? $settings['description']: $generatori[0]['descrizione'];

if (!$multiplo)
    dynamicMenu(caricaGeneratori($service, true), $settings['itemsMenu']);


session_start();

// Controlla se l'utente è loggato
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
