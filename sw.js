/**
 * Service Worker Root - Sistema CFC Bom Conselho
 * Wrapper para dar scope "/" ao Service Worker
 * 
 * Este arquivo está no root para permitir que o SW controle todo o site.
 * Ele importa o SW principal de /pwa/sw.js
 */

// Importar o Service Worker principal
importScripts('/pwa/sw.js');

// Este arquivo apenas delega para /pwa/sw.js
// O scope "/" é garantido por este arquivo estar no root
