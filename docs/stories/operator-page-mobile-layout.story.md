---
type: story
slug: operator-page-mobile-layout
title: "Pagina operatore leggibile sul telefono"
status: done
owner: Roosko
created: 2026-11-10
updated: 2026-11-10
---

# Pagina operatore leggibile sul telefono

## Problema (visto dal vivo, Pixel 7, 412 px)

- I bottoni tastiera e fotocamera mostravano a tutta larghezza il testo per screen reader ("Mostra o nascondi la
  tastiera..."): il CSS di Filament 5 non contiene l'utility `sr-only` di Tailwind.
- La griglia della pagina prendeva la larghezza minima del campo di scansione (472 px): campo, suggerimento e bottoni
  d'azione tagliati a destra.
- Il bordo del campo usava `rgb(var(--primary-500))`, ma in Filament 5 le variabili sono colori completi: nessun bordo.

## Correzione

Classe `roosko-op-sr-only` definita nella pagina; griglia `minmax(0, 1fr)` con figli `min-width: 0`; colori con
`var(--primary-500)`; contenitore della fotocamera in flex per bottoni della stessa altezza. Verificato con Playwright
(`scrollWidth` = viewport, nessun elemento oltre il bordo).
